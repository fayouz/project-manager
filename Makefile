DOCKER_COMPOSE = docker compose
PHP_SERVICE = php
PWA_SERVICE = pwa
DATABASE_SERVICE = database

PHP_EXEC = $(DOCKER_COMPOSE) exec $(PHP_SERVICE)
SYMFONY = $(PHP_EXEC) bin/console
COMPOSER = $(PHP_EXEC) composer

.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

##
## Docker Commands
##

start: ## Start the project in development mode
	$(DOCKER_COMPOSE) up -d --remove-orphans

stop: ## Stop the project
	$(DOCKER_COMPOSE) stop

down: ## Down the project (remove containers and networks)
	$(DOCKER_COMPOSE) down --remove-orphans

restart: stop start ## Restart the project

build: ## Build docker images
	$(DOCKER_COMPOSE) build --no-cache

logs: ## Show docker logs
	$(DOCKER_COMPOSE) logs -f

sh: ## Access the PHP container shell
	$(DOCKER_COMPOSE) exec $(PHP_SERVICE) sh

##
## Project Commands
##

install: ## Install project dependencies (composer + npm/pnpm/yarn)
	$(COMPOSER) install
	$(DOCKER_COMPOSE) exec $(PWA_SERVICE) npm install

cache-clear: ## Clear Symfony cache
	$(SYMFONY) cache:clear

db-migrate: ## Run database migrations
	$(SYMFONY) doctrine:migrations:migrate --no-interaction

db-diff: ## Generate a new migration by comparing the current database to your mapping information
	$(SYMFONY) doctrine:migrations:diff

db-reset: ## Reset the database (drop, create, migrate, fixtures)
	$(SYMFONY) doctrine:database:drop --force --if-exists
	$(SYMFONY) doctrine:database:create
	$(SYMFONY) doctrine:migrations:migrate --no-interaction
	$(SYMFONY) doctrine:fixtures:load --no-interaction

##
## Quality & Tests
##

test: ## Run tests
	$(PHP_EXEC) bin/phpunit

test-e2e: ## Run Playwright E2E tests in dedicated container
	$(DOCKER_COMPOSE) run --rm e2e npx playwright test tests/setup.spec.js --project=chromium

lint: ## Run linter (PHP-CS-Fixer if configured)
	$(PHP_EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff

##
## PWA
##

pwa-logs: ## Show PWA logs
	$(DOCKER_COMPOSE) logs -f $(PWA_SERVICE)

.PHONY: help start stop down restart build logs sh install cache-clear db-migrate db-diff db-reset test lint pwa-logs
