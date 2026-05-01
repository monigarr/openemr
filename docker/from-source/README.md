# OpenEMR from this repository (Docker)

Build and run OpenEMR from **your local clone** (for example `master`), not the pre-built `openemr/openemr:latest` image on Docker Hub.

The image extends [`openemr/openemr:flex`](https://hub.docker.com/r/openemr/openemr/) (Alpine-based) so PHP extensions, Apache, and container entrypoints match the usual OpenEMR Docker workflow. Node.js and build tools are added with `apk` during the image build.

## Build only (context = repository root)

```shell
docker build -f docker/from-source/Dockerfile -t openemr:from-source .
```

Optional labels:

```shell
docker build -f docker/from-source/Dockerfile -t openemr:from-source \
  --build-arg GIT_COMMIT="$(git rev-parse HEAD 2>/dev/null || echo unknown)" \
  --build-arg SOURCE_BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo master)" \
  .
```

## Run with MariaDB (same pattern as production compose)

From the **repository root**:

```shell
docker compose -f docker/from-source/docker-compose.yml up
```

Or from this directory:

```shell
docker compose -f docker-compose.yml up
```

First startup can take several minutes (database + OpenEMR setup). Default demo credentials match `docker/production/docker-compose.yml` (`admin` / `pass` unless you change `OE_USER` / `OE_PASS`). **Change all passwords and secrets before any real deployment.**

## Volumes

`docker-compose.yml` defines named volumes for MySQL data, OpenEMR `sites`, and logs—same idea as [docker/production/docker-compose.yml](../production/docker-compose.yml).

## More Docker documentation

See [DOCKER_README.md](../../DOCKER_README.md) for production vs development images on Docker Hub.
