serverName := "open-swiss-buildings-api.localhost"
dockerCompose := "docker compose"
dockerAppPhpExec := dockerCompose + " exec --user php app"
dockerAppPhpDebugExec := dockerCompose + " exec --user php --env XDEBUG_SESSION= app"
export SERVER_NAME := serverName

default:
    @just --list --justfile {{ justfile() }}

# Builds the Docker images
build *options:
    {{ dockerCompose }} build {{ options }}

# Builds the Docker images
build-pull: (build "--pull")

build-prod *options:
    # The following forces Docker to ignore the override file, and build the app-prod target image
    {{ dockerCompose }} --file compose.yaml build {{ options }}

# Builds the Docker images
rebuild: (build "--pull" "--no-cache")

# Start the docker containers in detached mode (no logs) and waits for the dependencies to be up and running.
up:
    {{ dockerCompose }} up --detach --wait

up-foreground:
    {{ dockerCompose }} up

fix-perms:
    {{ dockerCompose }} exec app chown php:nginx /www -R
    {{ dockerCompose }} exec app chmod go+rX /www -R

# Stop the running containers
down:
    {{ dockerCompose }} down --remove-orphans

logs:
    {{ dockerCompose }} logs --follow

shell:
    {{ dockerAppPhpExec }} zsh

# Open a shell into the container as PHP user, with `sh` as terminal (no fancy terminal nor history)
shell-php:
    {{ dockerAppPhpExec }} sh

shell-root:
    {{ dockerCompose }} exec app sh

init-test-database:
    {{ dockerCompose }} exec -Ti database psql -U ${POSTGRES_DB:-app} --no-password --dbname ${POSTGRES_DB:-app} <<< "DROP DATABASE ${POSTGRES_DB:-app}_test;"
    {{ dockerCompose }} exec -Ti database psql -U ${POSTGRES_DB:-app} --no-password --dbname ${POSTGRES_DB:-app} <<< "CREATE DATABASE ${POSTGRES_DB:-app}_test TEMPLATE template_postgis;"
    {{ dockerCompose }} exec --env APP_ENV=test --user php app bin/console doctrine:migrations:migrate -n

cs-check: (composer "cs:check")
cs-fix: (composer "cs:fix")
phpstan: (composer "phpstan:check")
phpunit *options: (composer "phpunit:check --" options)
phpunit-coverage *options: (composer "phpunit:coverage:html --" options)
phpunit-debug *options: (composer-debug "phpunit:check --" options)
rector-check: (composer "rector:check")
rector-fix: (composer "rector:fix")

# Execute all CI tasks by fixing CS and Rector reported errors
ci-fix: cs-fix rector-fix phpstan phpunit

# Execute all CI tasks: php-cs-fixer, phpstan, phpunit and rector
ci-check: cs-check rector-check phpstan phpunit

composer +options:
    {{ dockerAppPhpExec }} composer {{ options }}

composer-debug +options:
    {{ dockerAppPhpDebugExec }} composer {{ options }}

reset-all-data:
    {{ dockerAppPhpExec }} bin/console doctrine:schema:drop --force --quiet
    {{ dockerAppPhpExec }} bin/console doctrine:migrations:migrate --no-interaction
    {{ dockerAppPhpExec }} bin/console meilisearch:index:drop
    {{ dockerAppPhpExec }} bin/console meilisearch:index:reconfigure

console *command:
    {{ dockerAppPhpExec }} bin/console {{ command }}

messenger-consume +options:
    {{ dockerAppPhpExec }} bin/console messenger:consume {{ options }}

# Reset, prepare and resolve the given Job-ID
job-reset-resolve id:
    @echo Resetting
    {{ dockerAppPhpExec }} bin/console app:resolve:jobs:reset {{ id }}
    @echo Preparing
    {{ dockerAppPhpExec }} bin/console app:resolve:jobs:prepare {{ id }}
    @echo Resolving
    {{ dockerAppPhpExec }} bin/console app:resolve:jobs:resolve {{ id }}
