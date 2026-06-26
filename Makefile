MAIN_DIR := apps/main
SCAN_DIR := apps/scanner

.PHONY: help init install dev dev-main dev-scanner build lint \
        up down restart infra-up infra-down infra-restart infra-logs \
        migrate migrate-fresh seed fresh \
        queue schedule logs-app

# ─── Help ────────────────────────────────────────────────────

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-22s\033[0m %s\n", $$1, $$2}'

# ─── Init ────────────────────────────────────────────────────

init: ## First-time setup — copy .env, install deps, generate key, run migrations
	@echo "==> Copying .env file..."
	@cp -n $(MAIN_DIR)/.env.example $(MAIN_DIR)/.env || true
	@echo "==> Installing PHP dependencies..."
	cd $(MAIN_DIR) && composer install --no-interaction
	@echo "==> Installing JS dependencies..."
	pnpm install
	@echo "==> Generating Laravel APP_KEY..."
	cd $(MAIN_DIR) && php -c php.ini artisan key:generate --ansi
	@echo "==> Running migrations..."
	cd $(MAIN_DIR) && php -c php.ini artisan migrate
	@echo ""
	@echo "Done! Next steps:"
	@echo "  make infra-up  — start MySQL + Redis + phpMyAdmin + Mailpit"
	@echo "  make dev       — start all dev servers"

install: ## Install all dependencies (PHP + JS)
	cd $(MAIN_DIR) && composer install --no-interaction
	pnpm install

# ─── Dev ─────────────────────────────────────────────────────

dev: ## Run all dev servers (Laravel + Vite + Scanner)
	@npx concurrently \
		--names "laravel,vite,scanner" \
		--prefix-colors "blue,green,magenta" \
		"$(MAKE) -s _serve-laravel" \
		"$(MAKE) -s _serve-vite" \
		"$(MAKE) -s _serve-scanner"

dev-main: ## Run Laravel + Vite only
	@npx concurrently \
		--names "laravel,vite" \
		--prefix-colors "blue,green" \
		"$(MAKE) -s _serve-laravel" \
		"$(MAKE) -s _serve-vite"

dev-scanner: ## Run Scanner PWA only  (local → :3002)
	cd $(SCAN_DIR) && pnpm dev

build: ## Build all assets for production (Vite + Scanner)
	@echo "==> Building Vite assets..."
	cd $(MAIN_DIR) && npm run build
	@echo "==> Building Scanner..."
	cd $(SCAN_DIR) && pnpm build

lint: ## Lint all workspaces
	pnpm run lint

# ─── Docker — Infra (MySQL + Redis + phpMyAdmin + Mailpit) ───

infra-up: ## Start infra containers
	docker compose up -d

infra-down: ## Stop infra containers
	docker compose down

infra-restart: ## Restart infra containers
	docker compose restart

infra-logs: ## Tail infra container logs
	docker compose logs -f

up: infra-up ## Shorthand: infra-up
down: infra-down ## Shorthand: infra-down
restart: infra-restart ## Shorthand: infra-restart

# ─── Database ────────────────────────────────────────────────

migrate: ## Run migrations  (local)
	cd $(MAIN_DIR) && php -c php.ini artisan migrate

migrate-fresh: ## Fresh migration + seed  (local)
	cd $(MAIN_DIR) && php -c php.ini artisan migrate:fresh --seed

seed: ## Run seeders  (local)
	cd $(MAIN_DIR) && php -c php.ini artisan db:seed

fresh: migrate-fresh ## Alias: migrate:fresh --seed  (local)

# ─── Queue & Scheduler ───────────────────────────────────────

queue: ## Run Laravel queue worker  (local)
	cd $(MAIN_DIR) && php -c php.ini artisan queue:listen --tries=1 --timeout=0

schedule: ## Run Laravel scheduler loop  (local)
	cd $(MAIN_DIR) && php -c php.ini artisan schedule:work

# ─── Logs ────────────────────────────────────────────────────

logs-app: ## Tail Laravel application logs
	@tail -f $(MAIN_DIR)/storage/logs/laravel.log

# ─── Internal targets (jangan dipanggil langsung) ─────────────

_serve-laravel:
	cd $(MAIN_DIR) && php -c php.ini artisan serve

_serve-vite:
	cd $(MAIN_DIR) && npm run build -- --watch 2>/dev/null || cd $(MAIN_DIR) && npx vite

_serve-scanner:
	cd $(SCAN_DIR) && pnpm dev
