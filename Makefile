setup:
	@test -s phpunit.xml || cp phpunit.xml.dist phpunit.xml
	@docker-compose run --rm app composer install

destroy:
	@docker-compose down --remove-orphans --volumes

app:
	@docker-compose run --rm app sh

test:
	@docker-compose run --rm app sh -c "php ./vendor/bin/phpunit"
