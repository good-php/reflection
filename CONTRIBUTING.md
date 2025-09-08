# Commands

Here are some of the commands that you'll need:
 - install dependencies: `docker run -it --rm -v $PWD:/app -w /app chialab/php-dev:8.2 composer install`
 - run tests with phpunit: `docker run -it --rm -v $PWD:/app -w /app chialab/php-dev:8.2 composer test`
 - reformat using php-cs-fixer: `docker run -it --rm -v $PWD:/app -w /app chialab/php-dev:8.2 composer cs-fix`
 - analyse with phpstan: `docker run -it --rm -v $PWD:/app -w /app chialab/php-dev:8.2 composer phpstan`
 - benchmark: `docker run -it --rm -v $"($env.PWD):/app" -v $"($env.PWD)/misc/opcache.ini:/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini" -w /app chialab/php-dev:8.2 composer benchmark -- --output=html --report=all`
 - typhoon benchmark: `docker run -it --rm -v $"($env.PWD)/benchmark/typhoon:/app" -v $"($env.PWD)/misc/opcache.ini:/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini" -v $"($env.PWD)/tests/Stubs:/app/tests/Stubs" -w /app chialab/php-dev:8.2 composer benchmark`
