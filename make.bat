@echo off
REM ===================================================
REM  OCR Final - Docker Commands
REM  Usage: make [command]
REM ===================================================

IF "%1"=="" GOTO help
IF "%1"=="help" GOTO help
IF "%1"=="build" GOTO build
IF "%1"=="up" GOTO up
IF "%1"=="down" GOTO down
IF "%1"=="restart" GOTO restart
IF "%1"=="stop" GOTO stop
IF "%1"=="start" GOTO start
IF "%1"=="ps" GOTO ps
IF "%1"=="logs" GOTO logs
IF "%1"=="shell" GOTO shell
IF "%1"=="db-shell" GOTO db-shell
IF "%1"=="install" GOTO install
IF "%1"=="update" GOTO update
IF "%1"=="migrate" GOTO migrate
IF "%1"=="seed" GOTO seed
IF "%1"=="fresh" GOTO fresh
IF "%1"=="key" GOTO key
IF "%1"=="tinker" GOTO tinker
IF "%1"=="clear-all" GOTO clear-all
IF "%1"=="optimize" GOTO optimize
IF "%1"=="test" GOTO test
IF "%1"=="setup" GOTO setup
IF "%1"=="clean" GOTO clean
IF "%1"=="nuke" GOTO nuke

echo Unknown command: %1
echo Run "make help" to see available commands.
GOTO end

REM ——— HELP ———————————————————————————————————————

:help
echo.
echo   OCR Final - Docker Commands
echo   ===========================
echo.
echo   DOCKER:
echo     make build       Build all Docker images
echo     make up          Start all containers
echo     make down        Stop and remove all containers
echo     make restart     Restart all containers
echo     make stop        Stop containers (without removing)
echo     make start       Start previously stopped containers
echo     make ps          List running containers
echo     make logs        Show container logs (follow mode)
echo.
echo   SHELL ACCESS:
echo     make shell       Open bash in the app container
echo     make db-shell    Open MySQL shell in the db container
echo.
echo   COMPOSER:
echo     make install     Run composer install
echo     make update      Run composer update
echo.
echo   LARAVEL:
echo     make migrate     Run database migrations
echo     make seed        Run database seeders
echo     make fresh       Drop all tables, re-migrate and seed
echo     make key         Generate application key
echo     make tinker      Open Laravel Tinker REPL
echo.
echo   CACHE:
echo     make clear-all   Clear all caches
echo     make optimize    Cache config/routes/views for production
echo.
echo   TESTING:
echo     make test        Run PHPUnit tests
echo.
echo   SETUP / CLEANUP:
echo     make setup       Full first-time setup (build + start + install + key + migrate)
echo     make clean       Stop containers and remove volumes
echo     make nuke        Full reset (remove containers, volumes, and images)
echo.
GOTO end

REM ——— DOCKER —————————————————————————————————————

:build
echo Building Docker images...
docker compose build
GOTO end

:up
echo Starting containers...
docker compose up -d
GOTO end

:down
echo Stopping and removing containers...
docker compose down
GOTO end

:restart
echo Restarting containers...
docker compose restart
GOTO end

:stop
echo Stopping containers...
docker compose stop
GOTO end

:start
echo Starting containers...
docker compose start
GOTO end

:ps
docker compose ps
GOTO end

:logs
docker compose logs -f
GOTO end

REM ——— SHELL ACCESS ———————————————————————————————

:shell
echo Opening shell in app container...
docker exec -it ocr_app bash
GOTO end

:db-shell
echo Opening MySQL shell...
docker exec -it ocr_db mysql -uroot -psecret ocr
GOTO end

REM ——— COMPOSER ———————————————————————————————————

:install
echo Running composer install...
docker exec -it ocr_app composer install
GOTO end

:update
echo Running composer update...
docker exec -it ocr_app composer update
GOTO end

REM ——— LARAVEL ————————————————————————————————————

:migrate
echo Running migrations...
docker exec -it ocr_app php artisan migrate
GOTO end

:seed
echo Running seeders...
docker exec -it ocr_app php artisan db:seed
GOTO end

:fresh
echo Dropping all tables and re-running migrations with seeders...
docker exec -it ocr_app php artisan migrate:fresh --seed
GOTO end

:key
echo Generating application key...
docker exec -it ocr_app php artisan key:generate
GOTO end

:tinker
echo Opening Tinker...
docker exec -it ocr_app php artisan tinker
GOTO end

REM ——— CACHE ——————————————————————————————————————

:clear-all
echo Clearing all caches...
docker exec -it ocr_app php artisan cache:clear
docker exec -it ocr_app php artisan config:clear
docker exec -it ocr_app php artisan route:clear
docker exec -it ocr_app php artisan view:clear
echo All caches cleared!
GOTO end

:optimize
echo Optimizing for production...
docker exec -it ocr_app php artisan optimize
GOTO end

REM ——— TESTING ————————————————————————————————————

:test
echo Running tests...
docker exec -it ocr_app php artisan test
GOTO end

REM ——— SETUP / CLEANUP ———————————————————————————

:setup
echo.
echo  === Full First-Time Setup ===
echo.
call make.bat build
call make.bat up
call make.bat install
call make.bat key
call make.bat migrate
echo.
echo  Setup complete! App is running at http://localhost:8080
echo.
GOTO end

:clean
echo Stopping containers and removing volumes...
docker compose down -v
GOTO end

:nuke
echo Full reset — removing containers, volumes, and images...
docker compose down -v --rmi all --remove-orphans
GOTO end

:end
