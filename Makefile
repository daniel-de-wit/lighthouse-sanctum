php8.2:
	@test composer.lock || rm composer.lock
	@test -s phpunit.xml || cp phpunit.xml.dist phpunit.xml
	@docker-compose run --rm php8.2 composer install
	@docker-compose run --rm php8.2 sh

php8.3:
	@test composer.lock || rm composer.lock
	@test -s phpunit.xml || cp phpunit.xml.dist phpunit.xml
	@docker-compose run --rm php8.3 composer install
	@docker-compose run --rm php8.3 sh

php8.4:
	@test composer.lock || rm composer.lock
	@test -s phpunit.xml || cp phpunit.xml.dist phpunit.xml
	@docker-compose run --rm php8.4 composer install
	@docker-compose run --rm php8.4 sh
