# Vendored openemr-devops files

These files mirror [openemr/openemr-devops](https://github.com/openemr/openemr-devops) under `docker/openemr/8.1.1/`. They are copied into the production image by [`../Dockerfile`](../Dockerfile) so the Docker build does not need network access to GitHub for this slice.

## Last refresh

- **Source path in devops repo:** `docker/openemr/8.1.1/`
- **openemr-devops ref:** `master` at commit `e4ae86d0251dafaf8f2a388e511ce1588522c6ca` (2026-05-04).
- **PHP note:** Vendored `php.ini` is aligned with PHP 8.5 (`include_path` uses `/usr/share/php85`), matching `PHP_VERSION` in [`../Dockerfile`](../Dockerfile).

## How to refresh

1. Pick a ref (branch, tag, or commit SHA) in [openemr-devops](https://github.com/openemr/openemr-devops).
2. Replace the contents of `docker/openemr/8.1.1/` (or add a new version directory and bump `OPENEMR_DEVOPS_DIR` in the Dockerfile) from that ref, for example:
   - Clone devops and copy `docker/openemr/8.1.1/*` into `docker/agentforge-railway/upstream/docker/openemr/8.1.1/`, or
   - Download `https://codeload.github.com/openemr/openemr-devops/tar.gz/<REF>`, extract, and copy the same subtree.
3. If PHP minor or package layout changed upstream, align `PHP_VERSION` / `PHP_VERSION_ABBR` and `php.ini` in the vendored tree with [`../Dockerfile`](../Dockerfile).
4. Record the devops commit SHA in this file under **Last refresh**.
