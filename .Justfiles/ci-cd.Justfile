# Run PHP CS-Fixer check
[group('CI/CD')]
cs-check: (composer "cs:check")

# Run PHP CS-Fixer and fix issues
[group('CI/CD')]
cs-fix: (composer "cs:fix")

# Run PHPStan
[group('CI/CD')]
phpstan: (composer "phpstan:check")

# Run PHPUnit (with options)
[group('CI/CD')]
phpunit *options: (composer "phpunit:check --" options)

# Run PHPUnit and build HTML coverage report (with options)
[group('CI/CD')]
phpunit-coverage *options: (composer "phpunit:coverage:html --" options)

# Run PHPUnit with XDebug enabled (with options)
[group('CI/CD')]
phpunit-debug *options: (composer-debug "phpunit:check --" options)

# Run Rector checks
[group('CI/CD')]
rector-check: (composer "rector:check")

# Run Rector and fix issues
[group('CI/CD')]
rector-fix: (composer "rector:fix")

# Execute all CI tasks by fixing CS and Rector reported errors
[group('CI/CD')]
ci-fix: cs-fix rector-fix phpstan phpunit

# Execute all CI tasks: php-cs-fixer, phpstan, phpunit and rector
[group('CI/CD')]
ci-check: cs-check rector-check phpstan phpunit

[group('CI/CD')]
composer +options:
    {{ dockerAppPhpExec }} composer {{ options }}

[group('CI/CD')]
composer-debug +options:
    {{ dockerAppPhpDebugExec }} composer {{ options }}
