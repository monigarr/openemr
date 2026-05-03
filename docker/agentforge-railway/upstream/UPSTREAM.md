# Vendored openemr-devops files

These files mirror [openemr/openemr-devops](https://github.com/openemr/openemr-devops) under `docker/openemr/8.1.1/`. They are copied into the production image by [`../Dockerfile`](../Dockerfile) so the Docker build does not need network access to GitHub for this slice.

## Last refresh

- **Source path in devops repo:** `docker/openemr/8.1.1/`
- **Pin:** Update this section when you refresh (commit SHA or tag from openemr-devops).

## How to refresh

1. Pick a ref (branch, tag, or commit SHA) in [openemr-devops](https://github.com/openemr/openemr-devops).
2. Replace the contents of `docker/openemr/8.1.1/` (or add a new version directory and bump `OPENEMR_DEVOPS_DIR` in the Dockerfile) from that ref, for example:
   - Clone devops and copy `docker/openemr/8.1.1/*` into `docker/agentforge-railway/upstream/docker/openemr/8.1.1/`, or
   - Download `https://codeload.github.com/openemr/openemr-devops/tar.gz/<REF>`, extract, and copy the same subtree.
3. If PHP minor or package layout changed upstream, align `PHP_VERSION` / `PHP_VERSION_ABBR` and `php.ini` in the vendored tree with [`../Dockerfile`](../Dockerfile).
4. Record the devops commit SHA in this file under **Last refresh**.
