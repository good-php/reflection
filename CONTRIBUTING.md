# Commands

Here are some of the commands that you'll need:
 - install dependencies: `docker compose run -it --rm php composer install`
 - run tests with phpunit: `docker compose run -it --rm php composer test`
 - reformat using php-cs-fixer: `docker compose run -it --rm php composer cs-fix`
 - analyse with phpstan: `docker compose run -it --rm php composer phpstan`
 - benchmark: `docker compose run -it --rm benchmark-php composer benchmark -- --output=html --report=all`
 - typhoon benchmark: `docker compose run -it --rm -w /app/benchmark/typhoon benchmark-php composer benchmark -- --config=../phpbench.json --bootstrap=/app/benchmark/typhoon/vendor/autoload.php --output=html --report=all`
