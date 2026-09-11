<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver;
use SourceBroker\DeployerLoader\Load;

// vlucas/phpdotenv v2 (dépendance de sourcebroker/deployer-extended-database) utilise
// la directive ini "auto_detect_line_endings", dépréciée depuis PHP 8.1. Le gestionnaire
// d'erreurs de Deployer transforme toute déprécation en ErrorException fatale : on
// désactive donc ce niveau avant que EnvDriver ne lise le .env.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require 'recipe/symfony.php';
require_once __DIR__.'/vendor/autoload.php';

new Load([
    ['path' => 'vendor/sourcebroker/deployer-instance/deployer'],
    // arguments.php appelle argument(), une fonction retirée depuis Deployer 8 :
    // l'argument "stage" qu'elle déclarait pour l'aide CLI existe déjà nativement.
    ['path' => 'vendor/sourcebroker/deployer-extended-database/deployer', 'excludePattern' => '/^arguments\.php$/'],
]);

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

localhost('local')
    ->set('deploy_path', getcwd())
    ->set('bin/php', 'php')
    ->set('db_databases', [
        'database_default' => [
            (new EnvDriver())->getDatabaseConfig(),
        ],
    ]);

// Hosts
foreach (['preprod', 'prod'] as $env) {
    host($env)
        ->setHostname(getenv('DEPLOY_HOST'))
        ->setRemoteUser(getenv('DEPLOY_USER'))
        ->set('http_user', getenv('DEPLOY_USER'))
        ->set('writable_mode', 'chmod')
        ->setDeployPath(getenv('DEPLOY_PATH'))
        ->set('db_databases', [
            'database_default' => [
                (new EnvDriver())->getDatabaseConfig(),
            ],
        ])
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
    $cmd = "ssh $sshArgs {$user}@{$host} "
        ."\"tar -C {$sharedPath}/public -czf - uploads\" "
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
