`help:
	@echo "Available commands:"
	@echo "  build    - Build Docker containers"
	@echo "  start    - Start Docker containers"
	@echo "  stop     - Stop Docker containers"
	@echo "  restart  - Restart Docker containers"
	@echo "  shell    - Open bash shell in PHP container"
	@echo "  composer - Run composer command (e.g., make composer install)"
	@echo "  sf       - Run Symfony console command"
	@echo "  test     - Run tests"

first:
	docker-compose build

build:
	docker-compose build

start:
	docker-compose up -d

stop:
	docker-compose down

restart: stop start

shell:
	docker-compose exec php bash

composer:
	docker-compose exec php composer $(CMD)

sf:
	docker-compose exec php php bin/console $(CMD)

test:
	docker-compose exec php php bin/phpunit