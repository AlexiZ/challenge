<?php

namespace Deployer;

require 'recipe/symfony.php';
require_once __DIR__.'/vendor/autoload.php';

// Charger les secrets depuis un fichier local qui n’est pas versionné
if (file_exists(__DIR__.'/deploy_secrets.php')) {
    require __DIR__.'/deploy_secrets.php';
}

// Config
set('application', 'challenge');
set('repository', 'git@github.com:AlexiZ/challenge.git');
set('branch', 'main');
set('keep_releases', 5);
add('shared_dirs', ['public/uploads']);
// deploy:vendors passe --no-scripts en dur ; le recipe Symfony ne lance deploy:cache:clear
// que si "composer_options" contient --no-scripts (cf. vendor/deployer/deployer/recipe/symfony.php).
set('composer_options', '--no-scripts');

localhost('local')
    ->set('deploy_path', getcwd())
    ->set('bin/php', 'php');

// Hosts
foreach (['preprod', 'prod'] as $env) {
    host($env)
        ->setHostname(getenv('DEPLOY_HOST'))
        ->setRemoteUser(getenv('DEPLOY_USER'))
        ->set('http_user', getenv('DEPLOY_USER'))
        ->set('writable_mode', 'chmod')
        ->setDeployPath(getenv('DEPLOY_PATH'))
        ->setIdentityFile(getenv('DEPLOY_IDENTITY_FILE'))
        ->setForwardAgent(true)
        ->set('symfony_env', $env)
        ->setSshArguments([
            '-p '.getenv('DEPLOY_PORT'),
            '-o UserKnownHostsFile='.getenv('DEPLOY_KNOWN_HOSTS'),
            // Ignore ~/.ssh/config : dans le conteneur, dep tourne en root alors que ce
            // fichier appartient à l'utilisateur hôte, ce qu'OpenSSH refuse ("Bad owner
            // or permissions"). Identité et known_hosts sont déjà fournis explicitement.
            '-F /dev/null',
        ]);
}

// Tasks
task('deploy:cleanup-repo', function () {
    run('rm -rf {{deploy_path}}/.dep/repo');
});

task('deploy:vendors', function () {
    // --no-scripts : les auto-scripts Symfony Flex (cache:clear, assets:install, importmap:install)
    // s'exécutent via proc_open, désactivé sur cet hébergement cPanel. On les rejoue nous-mêmes
    // plus loin via des tâches Deployer classiques (SSH direct, sans proc_open).
    run('cd {{release_path}} && {{bin/composer}} install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --verbose');
});

task('deploy:dump-env', function () {
    // Fige APP_ENV/APP_DEBUG dans .env.local.php pour cet host (preprod vs prod) :
    // sans ça, l'app retombe sur le défaut committé APP_ENV=dev de .env, donc
    // APP_DEBUG=1, ce qui fait échouer strictement l'asset mapper (node_modules
    // absent en prod) au lieu de l'ignorer silencieusement.
    run('cd {{release_path}} && {{bin/composer}} dump-env {{symfony_env}}');
});

task('deploy:assets:install', function () {
    run('{{bin/console}} assets:install {{console_options}}');
});

task('deploy:importmap:install', function () {
    run('{{bin/console}} importmap:install {{console_options}}');
});

function parsePostgresUrl(string $url): array
{
    $parts = parse_url($url);

    return [
        'host' => $parts['host'],
        'port' => $parts['port'] ?? 5432,
        'user' => rawurldecode($parts['user'] ?? ''),
        'password' => rawurldecode($parts['pass'] ?? ''),
        'dbname' => ltrim($parts['path'] ?? '', '/'),
    ];
}

task('db:pull', function () {
    // La cible (get('release_path')) a déjà .env.local.php (généré par deploy:dump-env),
    // qui contient la DATABASE_URL résolue telle que l'app la lit vraiment en prod.
    $remote = parsePostgresUrl(run(
        '{{bin/php}} -r '
        .escapeshellarg("echo (require '{{current_path}}/.env.local.php')['DATABASE_URL'];")
    ));

    $dotenv = new \Symfony\Component\Dotenv\Dotenv();
    $dotenv->usePutenv()->loadEnv(getcwd().'/.env');
    $local = parsePostgresUrl(getenv('DATABASE_URL'));

    $remoteDumpPath = get('deploy_path').'/.dep/database/dump.sql';
    $localDumpPath = 'var/db_pull_dump.sql';

    run('mkdir -p '.escapeshellarg(dirname($remoteDumpPath)));
    run(sprintf(
        'pg_dump --no-owner --no-privileges --clean --if-exists -h %s -p %s -U %s %s -f %s',
        escapeshellarg($remote['host']),
        escapeshellarg((string) $remote['port']),
        escapeshellarg($remote['user']),
        escapeshellarg($remote['dbname']),
        escapeshellarg($remoteDumpPath)
    ), env: ['PGPASSWORD' => $remote['password']], timeout: null);

    // Pas de rsync côté hébergement cPanel (cf. download_medias/upload_medias plus bas) : scp comme le reste du fichier.
    $host = currentHost()->getHostname();
    $user = currentHost()->getRemoteUser();
    $sshArgs = ['-F /dev/null'];
    if ($port = getenv('DEPLOY_PORT')) {
        $sshArgs[] = '-P '.escapeshellarg($port);
    }
    if ($knownHosts = getenv('DEPLOY_KNOWN_HOSTS')) {
        $sshArgs[] = '-o UserKnownHostsFile='.escapeshellarg($knownHosts);
    }
    $scpArgs = implode(' ', $sshArgs);
    runLocally("scp $scpArgs {$user}@{$host}:".escapeshellarg($remoteDumpPath)." ".escapeshellarg($localDumpPath));
    run('rm -f '.escapeshellarg($remoteDumpPath));

    runLocally(sprintf(
        'psql -h %s -p %s -U %s -d %s -f %s',
        escapeshellarg($local['host']),
        escapeshellarg((string) $local['port']),
        escapeshellarg($local['user']),
        escapeshellarg($local['dbname']),
        escapeshellarg($localDumpPath)
    ), env: ['PGPASSWORD' => $local['password']], timeout: null);
    runLocally('rm -f '.escapeshellarg($localDumpPath));
})->desc('Pull the remote PostgreSQL database and restore it locally.');

task('deploy:assets', function () {
    $host = currentHost()->getHostname();
    $user = currentHost()->getRemoteUser();
    $path = get('release_path').'/public';

    // Arguments SSH dynamiques
    $sshArgs = ['-F /dev/null'];
    if ($port = getenv('DEPLOY_PORT')) {
        $sshArgs[] = '-P '.escapeshellarg($port);
    }
    if ($knownHosts = getenv('DEPLOY_KNOWN_HOSTS')) {
        $sshArgs[] = '-o UserKnownHostsFile='.escapeshellarg($knownHosts);
    }
    $scpArgs = implode(' ', $sshArgs);

    // Vider le cache local de compilation AssetMapper avant de recompiler : en APP_ENV=prod
    // (debug=false), le ConfigCache sous-jacent ne revérifie jamais la fraîcheur d'une entrée
    // déjà en cache (un fichier .php présent est considéré éternellement valide), donc un
    // fichier déjà compilé une fois (ex. app.js) ne reprend jamais en compte les imports
    // ajoutés depuis, même après plein de recompilations.
    runLocally('rm -rf var/cache/prod/asset_mapper');

    // Build local (AssetMapper : compile assets/ + node_modules mappés vers public/assets)
    runLocally('APP_ENV=prod php bin/console asset-map:compile --no-interaction');

    // Archive locale
    runLocally('tar czf build.tar.gz -C public assets');

    // SCP avec arguments dynamiques
    runLocally("scp $scpArgs build.tar.gz {$user}@{$host}:{$path}/build.tar.gz");

    // Extraction côté serveur
    run("cd {$path} && tar xzf build.tar.gz && rm build.tar.gz");

    // Nettoyage local
    runLocally('rm -f build.tar.gz');
});

task('download_medias', function () {
    $host = currentHost()->getHostname();
    $user = currentHost()->getRemoteUser();
    $port = getenv('DEPLOY_PORT');
    $knownHosts = getenv('DEPLOY_KNOWN_HOSTS');

    // Côté remote : shared/public
    $sharedPath = get('current_path').'/../../shared';

    // Options SSH dynamiques
    $sshOptions = ['-F /dev/null'];
    if ($port) {
        $sshOptions[] = '-p '.escapeshellarg($port);
    }
    if ($knownHosts) {
        $sshOptions[] = '-o UserKnownHostsFile='.escapeshellarg($knownHosts);
    }
    $sshArgs = implode(' ', $sshOptions);

    // Remote → local
    // --ignore-failed-read : certains uploads appartiennent à un autre utilisateur que
    // {$user} (ex. process web) et ne sont pas lisibles en SSH ; on récupère le reste
    // plutôt que d'échouer entièrement (exit code 2) sur ces fichiers-là.
    $cmd = "ssh $sshArgs {$user}@{$host} "
        ."\"tar --ignore-failed-read -C {$sharedPath}/public -czf - uploads\" "
        .'| tar -xzf - -C public';

    runLocally($cmd);
});

task('upload_medias', function () {
    $host = currentHost()->getHostname();
    $user = currentHost()->getRemoteUser();
    $port = getenv('DEPLOY_PORT');
    $knownHosts = getenv('DEPLOY_KNOWN_HOSTS');
    $sharedPath = get('current_path').'/../../shared';

    // Construction des options SSH dynamiques
    $sshOptions = ['-F /dev/null'];
    if ($port) {
        $sshOptions[] = '-p '.escapeshellarg($port);
    }
    if ($knownHosts) {
        $sshOptions[] = '-o UserKnownHostsFile='.escapeshellarg($knownHosts);
    }
    $sshArgs = implode(' ', $sshOptions);

    $cmd = "tar -C public -czf - uploads | ssh $sshArgs {$user}@{$host} \"tar -xzf - -C {$sharedPath}/public\"";

    runLocally($cmd);
});

// Main deploy flow
task('deploy', [
    'deploy:unlock',
    'deploy:cleanup-repo',
    'deploy:prepare',
    'deploy:vendors',
    'deploy:dump-env',
    'deploy:assets:install',
    'deploy:importmap:install',
    'database:migrate',
    'deploy:assets',
    'deploy:cache:clear',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
after('db:pull', 'download_medias');
