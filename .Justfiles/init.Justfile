[group('Init')]
init-local: init-local-database init-local-meilisearch

# Init the database (drop the schema, run migrations)
[group('Init')]
init-local-database:
    {{ dockerAppPhpExec }} bin/console doctrine:schema:drop --force --quiet
    {{ dockerAppPhpExec }} bin/console doctrine:migrations:migrate --no-interaction

# Init the search index (drop the index and reconfigure it)
[group('Init')]
init-local-meilisearch:
    {{ dockerAppPhpExec }} bin/console meilisearch:index:drop
    {{ dockerAppPhpExec }} bin/console meilisearch:index:reconfigure

# Init the test database (drop the schema, run migrations)
[group('Init')]
init-test-database:
    {{ dockerCompose }} exec -Ti database psql -U ${POSTGRES_DB:-app} --no-password --dbname ${POSTGRES_DB:-app} -c "DROP DATABASE ${POSTGRES_DB:-app}_test;" || true
    {{ dockerCompose }} exec -Ti database psql -U ${POSTGRES_DB:-app} --no-password --dbname ${POSTGRES_DB:-app} -c "CREATE DATABASE ${POSTGRES_DB:-app}_test TEMPLATE template_postgis;"
    {{ dockerCompose }} exec --env APP_ENV=test --user php app bin/console doctrine:migrations:migrate -n
