# ===================================================
# OCR Final - Docker Makefile
# ===================================================
# Usage: make <target>
# Run `make help` to see all available commands.
# ===================================================

# Docker Compose command
DC = docker compose

# Container names
APP_CONTAINER = ocr_app
DB_CONTAINER  = ocr_db
WEB_CONTAINER = ocr_web

# Colors for help output
GREEN  = \033[0;32m
YELLOW = \033[0;33m
CYAN   = \033[0;36m
RESET  = \033[0m

.PHONY: help build up down restart stop start logs \
        shell db-shell \
        install migrate seed fresh \
        cache-clear config-clear route-clear view-clear optimize \
        test \
        ps clean nuke

## ——— Docker ———————————————————————————————————————

help: ## Show this help message
	@echo ""
	@echo "  OCR Final — Docker Commands"
	@echo "  ==========================="
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}'
	@echo ""

build: ## Build all Docker images
	$(DC) build

up: ## Start all containers in detached mode
	$(DC) up -d

down: ## Stop and remove all containers
	$(DC) down

restart: ## Restart all containers
	$(DC) restart

stop: ## Stop all containers (without removing)
	$(DC) stop

start: ## Start previously stopped containers
	$(DC) start

ps: ## List running containers
	$(DC) ps

logs: ## Show logs from all containers (follow mode)
	$(DC) logs -f

logs-app: ## Show logs from the app container
	$(DC) logs -f app

logs-web: ## Show logs from the web (nginx) container
	$(DC) logs -f web

logs-db: ## Show logs from the database container
	$(DC) logs -f db

## ——— App (Shell Access) ——————————————————————————

shell: ## Open a bash shell in the app container
	docker exec -it $(APP_CONTAINER) bash

db-shell: ## Open a MySQL shell in the database container
	docker exec -it $(DB_CONTAINER) mysql -uroot -psecret ocr

## ——— Composer ————————————————————————————————————

install: ## Run composer install inside the app container
	docker exec -it $(APP_CONTAINER) composer install

update: ## Run composer update inside the app container
	docker exec -it $(APP_CONTAINER) composer update

## ——— Laravel / Artisan ———————————————————————————

migrate: ## Run database migrations
	docker exec -it $(APP_CONTAINER) php artisan migrate

seed: ## Run database seeders
	docker exec -it $(APP_CONTAINER) php artisan db:seed

fresh: ## Drop all tables and re-run migrations + seeders
	docker exec -it $(APP_CONTAINER) php artisan migrate:fresh --seed

key: ## Generate application key
	docker exec -it $(APP_CONTAINER) php artisan key:generate

tinker: ## Open Laravel Tinker REPL
	docker exec -it $(APP_CONTAINER) php artisan tinker

## ——— Cache & Optimization ————————————————————————

cache-clear: ## Clear application cache
	docker exec -it $(APP_CONTAINER) php artisan cache:clear

config-clear: ## Clear config cache
	docker exec -it $(APP_CONTAINER) php artisan config:clear

route-clear: ## Clear route cache
	docker exec -it $(APP_CONTAINER) php artisan route:clear

view-clear: ## Clear compiled view files
	docker exec -it $(APP_CONTAINER) php artisan view:clear

clear-all: ## Clear all caches (app, config, route, view)
	docker exec -it $(APP_CONTAINER) php artisan cache:clear
	docker exec -it $(APP_CONTAINER) php artisan config:clear
	docker exec -it $(APP_CONTAINER) php artisan route:clear
	docker exec -it $(APP_CONTAINER) php artisan view:clear

optimize: ## Cache config, routes, and views for production
	docker exec -it $(APP_CONTAINER) php artisan optimize

## ——— Testing —————————————————————————————————————

test: ## Run PHPUnit tests
	docker exec -it $(APP_CONTAINER) php artisan test

## ——— Setup & Cleanup —————————————————————————————

setup: build up install key migrate ## Full first-time setup (build, start, install, key, migrate)
	@echo ""
	@echo "  ✅  Setup complete! App is running at http://localhost:8080"
	@echo ""

clean: ## Stop containers and remove volumes
	$(DC) down -v

nuke: ## Full reset — remove containers, volumes, images, and orphans
	$(DC) down -v --rmi all --remove-orphans
