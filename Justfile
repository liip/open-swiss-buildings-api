serverName := "open-swiss-buildings-api.localhost"
dockerCompose := "docker compose"
dockerAppPhpExec := dockerCompose + " exec --user php app"
dockerAppPhpDebugExec := dockerCompose + " exec --user php --env XDEBUG_SESSION= app"
export SERVER_NAME := serverName

import '.Justfiles/app.Justfile'
import '.Justfiles/ci-cd.Justfile'
import '.Justfiles/container.Justfile'
import '.Justfiles/init.Justfile'

default:
    @just --list --justfile {{ justfile() }}
