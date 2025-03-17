# Builds the Docker images
[group('Containers')]
build *options:
    {{ dockerCompose }} build {{ options }}

# Builds the Docker images
[group('Containers')]
build-pull: (build "--pull")

# Builds the Docker container for the PROD image
[group('Containers')]
build-prod *options:
    # The following forces Docker to ignore the override file, and build the app-prod target image
    {{ dockerCompose }} --file compose.yaml build {{ options }}

# Builds the Docker images
[group('Containers')]
rebuild: (build "--pull" "--no-cache")

# Start the docker containers in detached mode (no logs) and waits for the dependencies to be up and running.
[group('Containers')]
up:
    {{ dockerCompose }} up --detach --wait

# Start the docker containers in foreground (no deamon), showing and following logs
[group('Containers')]
up-foreground:
    {{ dockerCompose }} up

# Stop the running containers
[group('Containers')]
down:
    {{ dockerCompose }} down --remove-orphans

[group('Containers')]
logs:
    {{ dockerCompose }} logs --follow

[group('Containers')]
shell:
    {{ dockerAppPhpExec }} zsh

# Open a shell into the container as PHP user, with `sh` as terminal (no fancy terminal nor history)
[group('Containers')]
shell-php:
    {{ dockerAppPhpExec }} sh

[group('Containers')]
shell-root:
    {{ dockerCompose }} exec app sh
