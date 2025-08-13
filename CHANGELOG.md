# Changelog

# 2.x

## v2.0.9 (unreleased)

* [dx] Better error messages when trying to import registry data without downloading registry data first.

## v2.0.8

* [performance] Force using index when updating tasks.
* [bugfix] Avoid duplicate key errors when street abbreviated is the same as full street.

## v2.0.7

* [optimization] Optimized queries for address matching and geojson and added an additional index.

## v2.0.6

* [optimization] Rewrote how we create address resolution tasks in the database to batch insert to reduce number of queries.

## v2.0.5

* [optimization] Rewrote how we create tasks in the database to batch insert to reduce number of queries.
* [feature] Added `--format none` to the result printer, so that `app:resolve:jobs:one-shot` can be
  used to fully resolve but not read the result.

## v2.0.4

* [feature] Added `--format csv-file` and `--format json-file` to the console result
  printer, so that `app:resolve:jobs:one-shot` and `app:resolve:results:list`
  can print to a file in addition to the cli.
  This feature uses the `JobResultResponseCreatorInterface` so the contents
  are exactly the same as when fetched through the web interface.

## v2.0.3

* [feature] Progress bar when manually importing data
* [chore] Codebase cleanups and removed unused code

## v2.0.2

* [chore] Bump various Symfony libraries to from 7.2.5 to 7.2.6
* [doc] Mention command line by @dbu in #168

## v2.0.1

* [chore] PHPUnit: upgrade to v12.1 by @thePanz in #156
* [chore] Symfony: update symfony recipes by @thePanz in #157
* [optimization] FederalData: remove legacy commands, replaced by Registry ones by @thePanz in #154

## v2.0.0

* [chore] Upgrade to Meilisearch v1.13 by @thePanz in #104
* [dev] Docker: allow local-dev image to override PHP user's UID and GID by @thePanz in #106
* [dev] Docker: improve documentation on compose.override.example.yaml by @thePanz in #107
* [dev]Docker: ensure additional PHP configs are readable in local image by @thePanz in #108
* [chore] Upgrade to PHP v8.4 by @thePanz in #105
* [chore] Fix PHP v8.4 deprecation notices on CSV functions by @thePanz in #116
* [dev] Split 'just' commands into small files under .Justfiles folder by @thePanz in #117
* [performance] Meilisearch: index updatedAt on Address model as yyyymmdd by @thePanz in #119
* [chore] Models: unify language and country-code enums by @thePanz in #120
* [doc] AddressSearch: document query string by @thePanz in #122
* [feature] Introduce Registry domain, and implement CH and LI providers by @thePanz in #121
* [doc] Update documentation for LI by @thePanz in #127
* [performance] Configure worker-1 for resolving tasks only by @thePanz in #128
* [performance] Resolving: move resolving tasks into own messenger queue by @thePanz in #129
* [doc] ApiDoc: add documentation for Liechtenstein GWR in API-Doc by @thePanz in #143

## v1.0.1

* [chore] Build production image by @mjanser in #22
* [bugfix] Fix service dependencies by @mjanser in #23
* [dev] Provide environment to services by @mjanser in #24

## v1.0.0

Initial release.
