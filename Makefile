# —— Inspired by ———————————————————————————————————————————————————————————————
# http://fabien.potencier.org/symfony4-best-practices.html
# https://speakerdeck.com/mykiwi/outils-pour-ameliorer-la-vie-des-developpeurs-symfony?slide=47
# https://blog.theodo.fr/2018/05/why-you-need-a-makefile-on-your-project/
# Setup ————————————————————————————————————————————————————————————————————————

# Parameters
SHELL         = sh
PROJECT       = Challenge Vélo & Marche
GIT_AUTHOR    = AlexiZ
HTTP_PORT     = 8080
remote        ?= preprod
REMOTE        ?= $(remote)

# Executables
EXEC_PHP      = php
COMPOSER      = composer
GIT           = git
DEP           = docker compose exec -u $(shell id -u):$(shell id -g) php vendor/bin/dep

# Alias
SYMFONY       = $(EXEC_PHP) bin/console

# Executables: vendors
PHPUNIT       = ./vendor/bin/phpunit

# Misc
.DEFAULT_GOAL = help
.PHONY        : # Not needed here, but you can put your all your targets to be sure
                # there is no name conflict between your files and your targets.

## —— 🐝 The Symfony Makefile 🐝 ———————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

ssh: ## Connect to server with SSH
	@$(DEP) ssh $(REMOTE)

## —— Composer 🧙‍♂️ ————————————————————————————————————————————————————————————
install: composer.lock ## Install vendors according to the current composer.lock file
	@$(COMPOSER) install --no-progress --prefer-dist --optimize-autoloader

update: ## Update vendors
	@$(COMPOSER) update

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands
	@$(SYMFONY)

cc: ## Clear the cache. DID YOU CLEAR YOUR CACHE????
	@$(SYMFONY) c:c

warmup: ## Warmup the cache
	@$(SYMFONY) cache:warmup

fix-perms: ## Fix permissions of all var files
	@chmod -R 777 var/*

assets: purge ## Install the assets with symlinks in the public folder
	@$(SYMFONY) assets:install public/

purge: ## Purge cache and logs
	@rm -rf var/cache/* var/logs/*

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
docker-up: ## Build and start the app (PHP, PostgreSQL, Adminer, Mailpit) in Docker, HTTPS included
	@docker compose up --build -d

docker-down: ## Stop the Docker stack
	@docker compose down

## —— Project 🐝 ———————————————————————————————————————————————————————————————
start: docker-up load-fixtures ## Build/start the Docker stack and apply migrations

commands: ## Display all commands in the project namespace
	@$(SYMFONY) list $(PROJECT)

load-fixtures: ## Create the DB if needed and apply migrations
	@$(SYMFONY) doctrine:database:create --if-not-exists
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction

database: ## Pull REMOTE database to local instance
	@$(DEP) db:pull $(REMOTE)

medias: ## Download uploaded medias from REMOTE
	@$(DEP) download_medias $(REMOTE)

push-uploads: ## Push local uploaded medias to REMOTE
	@$(DEP) upload_medias $(REMOTE)

## —— Tests ✅ —————————————————————————————————————————————————————————————————
test: ## Run tests
	@$(PHPUNIT) --stop-on-failure --configuration phpunit.dist.xml

## —— Deploy 🚀 ————————————————————————————————————————————————————————————————
deploy: ## Full no-downtime deployment with deployphp/deployer
	@test -z "`git status --porcelain`"                 # Prevent deploy if there are modified or added files
	@test -z "`git diff --stat --cached origin/main`"   # Prevent deploy if there is something to push on main
	@$(DEP) deploy $(REMOTE)		                	# Deploy with deployphp/deployer

## —— Stats 📜 —————————————————————————————————————————————————————————————————
stats: ## Commits by the hour for the main author of this project
	@$(GIT) log --author="$(GIT_AUTHOR)" --date=iso | perl -nalE 'if (/^Date:\s+[\d-]{10}\s(\d{2})/) { say $$1+0 }' | sort | uniq -c|perl -MList::Util=max -nalE '$$h{$$F[1]} = $$F[0]; }{ $$m = max values %h; foreach (0..23) { $$h{$$_} = 0 if not exists $$h{$$_} } foreach (sort {$$a <=> $$b } keys %h) { say sprintf "%02d - %4d %s", $$_, $$h{$$_}, "*"x ($$h{$$_} / $$m * 50); }'
