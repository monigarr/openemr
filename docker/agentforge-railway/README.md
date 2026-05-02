# OpenEMR Docker image (custom branch / Railway)

This directory is **only** for building an image from **your** checkout so custom code (for example under `interface/modules/custom_modules/`) is included. It does not replace or modify the community `docker/from-source` or `docker/production` definitions.

## Branch

The default image label assumes branch **`prd_1_agentforge_monigarr`**. The Dockerfile does not run `git checkout`; whatever files are in the build context are copied in. On Railway, set the connected Git branch to `prd_1_agentforge_monigarr` (or merge your module there) so that branch is what gets built.

## Build locally (from repository root)

```shell
git checkout prd_1_agentforge_monigarr
docker build -f docker/agentforge-railway/Dockerfile -t openemr:agentforge .
```

Optional build args (for image metadata only):

```shell
docker build -f docker/agentforge-railway/Dockerfile -t openemr:agentforge \
  --build-arg GIT_COMMIT="$(git rev-parse HEAD 2>/dev/null || echo unknown)" \
  --build-arg SOURCE_BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo prd_1_agentforge_monigarr)" \
  .
```

## Railway.com

1. Create a service from your GitHub repo.
2. Set the deployment **branch** to `prd_1_agentforge_monigarr` (or the branch that contains your module).
3. Set **root directory** to the repository root (leave empty if the whole repo is the service root).
4. Set **Dockerfile path** to `docker/agentforge-railway/Dockerfile`.
5. Use a **MySQL or MariaDB** plugin (or second service) and set OpenEMR database env vars the same way as [docker/production/docker-compose.yml](../production/docker-compose.yml) (`MYSQL_HOST`, `MYSQL_ROOT_PASS`, etc.). OpenEMR’s Docker entrypoint expects those variables.
6. **HTTP:** The image exposes ports **80** and **443** like other OpenEMR flex-based images. Map Railway’s public HTTP to the port your process listens on (often **80** inside the container). If Railway injects `PORT`, confirm against [OpenEMR Docker Hub](https://hub.docker.com/r/openemr/openemr/) docs for your base image behavior.
7. **Persistence:** Mount or provision volumes for `sites/` and database data for anything beyond a demo.

## Security

Change default database and OpenEMR admin passwords before any real data. Do not commit secrets.

## Related

- Generic “build from clone” without a branch-specific path: [docker/from-source/README.md](../from-source/README.md).
