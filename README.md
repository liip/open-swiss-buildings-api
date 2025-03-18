# Open Swiss Buildings API

This is an API for the [Swiss Buildings Register](https://www.housing-stat.ch/) (Eidgenössisches Gebäude- und Wohnungsregister GWR).
The API dataset has been extended to also include building information taken from the
[Liechtenstein Building Registry](https://www.statistikportal.li/de/erhebungen-register/gebaeude-und-wohnungsregister).

It provides ways to resolve input data into address lists.
Additionally, it provides an autocomplete-search for addresses.

## Background

The application fetches the data about buildings from the two registries (for Switzerland and Liechtenstein) and stores
them in a PostgreSQL database.

For the autocomplete and address resolving, the data is also stores in a search engine.

## Usage

The API is able to resolve various input data into lists of addresses containing the building ID (EGID or GEID) and its coordinates.

Each import has some required columns.
Additional columns will be copied to the output on the corresponding lines.
In case of duplicates with different values for the same result, the values are separated with `||` in the output.

The return to data submission is a status with an `id`. Use the id to poll the job status and once it succeeded, you can
fetch the result from the API.

The address resolution could work like this, for example:

```
street_housenumbers,swisszipcode,town,extrainformation
Limmatstrasse 111,8005,Zürich,
Limmatstrasse 112,8005,Zürich,B
Limmatstrasse 114,8005,Zürich,
Limmatstrasse 119,8005,Zürich,X
Limmatstrasse 183,8005,Zürich,A
```

Once the resolve process is finished, the result can be fetched and would look like this:
```
id,confidence,country_code,egid,edid,municipality_code,postal_code,locality,street_name,street_house_number,latitude,longitude,match_type,original_address,extrainformation
018ef6f9-5301-72f0-a0e6-c4170dcdade0,1,CH,150404,0,261,8005,Zürich,Limmatstrasse,112,47.383714644865,8.5333052733667,exact,"Limmatstrasse 112, 8005 Zürich",B
018ef6f9-5305-792b-a63d-9674ef070492,1,CH,150427,0,261,8005,Zürich,Limmatstrasse,119,47.383946709755,8.5322481218705,exact,"Limmatstrasse 119, 8005 Zürich",X
018ef700-c19d-7dfe-a0c1-308b327c44e3,1,CH,2366055,0,261,8005,Zürich,Limmatstrasse,183,47.386170922358,8.5292387777084,exact,"Limmatstrasse 183, 8005 Zürich",A
018ef701-f436-7990-953a-ea2159eb31a5,1,CH,9011206,0,261,8005,Zürich,Limmatstrasse,111,47.383750821972,8.5325010116967,exact,"Limmatstrasse 111, 8005 Zürich",
018ef702-2560-7137-be1e-4134b02356d2,1,CH,9083913,0,261,8005,Zürich,Limmatstrasse,114,47.383955253925,8.5333727812119,exact,"Limmatstrasse 114, 8005 Zürich",
```

## Glossary

The naming is inspired from [Schema.org](https://schema.org/), especially the one for [postal addresses](https://schema.org/PostalAddress).

* **Municipality**

  A political location with a certain degree of self-government.
  In German, this is "Gemeinde".

* **Locality**

  The locality in which the address is, usually the name of the city or village.

* **Postal code**

  The postal code for the address.
  In German, this is "Postleitzahl (PLZ)".

* **Street name**

  The name of the street of the address, without the house number, e.g. `Limmatstrasse`.

* **Street house number**

  The house number of the address, e.g. `3b`.

* **Street house number (internally as an `integer`)**

  The plain house number of the address, without any suffix, e.g. `3`.

* **Street house number suffix**

  The suffix of a house number of the address, e.g. `b`.

* **Resolving**

  Specifies the process of finding addresses based on an input file.

* **Address matching**

  Specifies the process of matching addresses from an input file with addresses in the register.

* **Normalized**

  Some fields are normalized internally, which means special characters are removed.
  This makes it possible to handle different writings on a database level, e.g. lowercase vs. uppercase.

## Deployment

To deploy this application, you need the following services/containers:
* This application
* PostgreSQL (with GIS extension)
* Meilisearch

The configuration of the application is done using environment variables.
Make sure to define at least the following variables.

* APP_ENV (`prod`)
* APP_SECRET (randomly generated string)
* DATABASE_URL (`postgresql://[user]:[password]@[host]:[port]/[database]?serverVersion=[version]&charset=utf8`)
* MEILISEARCH_DSN (`https://[host]?apiKey=[key]`)

You can also refer to the Docker Compose setup in this repository used for local development.

### Database migrations

When deploying a new version of this application, the database might need to be migrated.
The official image handles this automatically.

If you installed this application in a different way, you need to make sure that the following command
is run once inside the freshly deployed application.

```
./bin/console doctrine:migrations:migrate -n
```

### Workers

The application uses workers to handle the resolving jobs asynchronously.
If you don't use the official image, you need to run the following commands as services.

```
./bin/console messenger:consume --limit=10 async
./bin/console messenger:consume --limit=10 scheduler_default
```

## Contributing

The project uses [Docker Compose](https://docs.docker.com/compose/) for the local development.
Copy `compose.override.example.yaml` to `compose.override.yaml` and adjust the file to your needs.

To make the local setup easier to handle, [just](https://just.systems/man/en/) is ready to be used.
When installed, run `just` inside the project to get a list of available commands.

For the initial setup, you should run the following commands:

* `cp phpstan.example.neon phpstan.neon` (and adjust it to your needs)
* `just rebuild`
* `just up`
* `just init-test-database`

After that, the application is running locally. You can access it on http://localhost.

To run all the static analysis and tests, `just ci-check` or `just ci-fix` can be used.
