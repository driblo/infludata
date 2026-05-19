.PHONY: help up down restart logs sh psql redis-cli artisan migrate fresh test stan lint format mobile-get mobile-test ci

help:
	@echo "infludata dev commands"
	@echo "  make up         start the dev stack"
	@echo "  make down       stop and remove containers"
	@echo "  make restart    restart the stack"
	@echo "  make logs       tail logs"
	@echo "  make sh         shell into the app container"
	@echo "  make psql       psql into postgres"
	@echo "  make artisan c=...  run an artisan command"
	@echo "  make migrate    run migrations"
	@echo "  make fresh      drop + recreate db schema"
	@echo "  make test       run backend pest tests"
	@echo "  make stan       run larastan"
	@echo "  make lint       run pint --test"
	@echo "  make format     run pint"
	@echo "  make mobile-get   npm ci in mobile/"
	@echo "  make mobile-test  jest"
	@echo "  make mobile-typecheck  tsc --noEmit"
	@echo "  make mobile-lint  eslint ."
	@echo "  make mobile-start expo start"
	@echo "  make ci         run the full CI suite locally"

up:
	@test -f backend/.env || cp backend/.env.example backend/.env
	docker compose up -d --build
	@echo "Waiting for postgres..." && sleep 3
	docker compose exec -T app sh -c "composer install --no-interaction --no-progress"
	docker compose exec -T app php artisan key:generate --force
	docker compose exec -T app php artisan migrate --force
	@echo "Up. API: http://localhost:8000  Horizon: http://localhost:8000/horizon  Mailpit: http://localhost:8025  MinIO: http://localhost:9001"

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f --tail=200

sh:
	docker compose exec app sh

psql:
	docker compose exec postgres psql -U infludata -d infludata

redis-cli:
	docker compose exec redis redis-cli

artisan:
	docker compose exec app php artisan $(c)

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh

test:
	docker compose exec -T app vendor/bin/pest

stan:
	docker compose exec -T app vendor/bin/phpstan analyse --memory-limit=1G

lint:
	docker compose exec -T app vendor/bin/pint --test

format:
	docker compose exec -T app vendor/bin/pint

mobile-get:
	cd mobile && npm ci

mobile-test:
	cd mobile && npm test

mobile-typecheck:
	cd mobile && npx tsc --noEmit

mobile-lint:
	cd mobile && npx eslint .

mobile-start:
	cd mobile && npx expo start

ci: lint stan test mobile-typecheck mobile-lint mobile-test
