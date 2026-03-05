## 8. Makefile для удобства (опционально)

.PHONY: up down restart build test migrate clean logs

up:
	docker-compose up -d --build

down:
	docker-compose down

restart:
	docker-compose restart

build:
	docker-compose build --no-cache

test:
	docker-compose exec php vendor/bin/phpunit

test-coverage:
	docker-compose exec php vendor/bin/phpunit --coverage-html tests/_output/coverage

migrate:
	docker-compose exec php php yii migrate --interactive=0

clean:
	docker-compose down -v
	rm -rf vendor/
	composer install

logs:
	docker-compose logs -f

shell:
	docker-compose exec php bash

db-shell:
	docker-compose exec db psql -U user -d loans
