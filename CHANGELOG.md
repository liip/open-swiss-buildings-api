# Changelog

# 2.x

## v2.0.4

* Added `--format csv-file` and `--format json-file` to the console result
  printer, so that `app:resolve:jobs:one-shot` and `app:resolve:results:list`
  can print to a file in addition to the cli.
  This feature uses the `JobResultResponseCreatorInterface` so the contents
  are exactly the same as when fetched through the web interface.

## v2.0.3

* Progress bar when manually importing data
* Codebase cleanups and removed unused code
