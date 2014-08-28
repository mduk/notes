test:: phpunit

composer: composer.json
	./composer.phar install

phpunit: composer phpunit.xml
	./vendor/bin/phpunit

clean:
	rm -rf ./vendor

