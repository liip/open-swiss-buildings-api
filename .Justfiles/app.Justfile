# Run the given command via bin/console
[group('Application')]
console *command:
    {{ dockerAppPhpExec }} bin/console {{ command }}

# Run the messenger:consume command, with the given options
[group('Application')]
messenger-consume +options:
    {{ dockerAppPhpExec }} bin/console messenger:consume {{ options }}

# Reset, prepare and resolve the given Job-ID
[group('Application')]
job-reset-resolve id:
    @echo Resetting
    {{ dockerAppPhpExec }} bin/console app:resolve:jobs:reset {{ id }}
    @echo Preparing
    {{ dockerAppPhpExec }} bin/console app:resolve:jobs:prepare {{ id }}
    @echo Resolving
    {{ dockerAppPhpExec }} bin/console app:resolve:jobs:resolve {{ id }}
