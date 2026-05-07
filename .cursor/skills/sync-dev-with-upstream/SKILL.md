---
name: sync-dev-with-upstream
description: >-
  Refreshes the current development branch with the latest upstream OpenEMR
  changes without merging dev work into master. Use when syncing this fork’s
  feature or dev branch with upstream, updating local master from upstream, or
  avoiding pushes of dev to master; full teammate steps live in docs/
  SYNC_DEV_WITH_UPSTREAM.md.
---

# Sync dev with upstream (this repository)

**Canonical doc (share with teammates):** [docs/SYNC_DEV_WITH_UPSTREAM.md](../../../docs/SYNC_DEV_WITH_UPSTREAM.md)

When helping with Git sync in this repo:

1. **Read and follow** `docs/SYNC_DEV_WITH_UPSTREAM.md` from the workspace root unless the user gives different remote or branch names.
2. **Do not** merge the dev/feature branch into `master` to sync, and **do not** push dev-only history to `origin master` as part of routine work.

## Policy (verbatim)

Merge the latest changes from the upstream repository into our current dev branch so that we can keep all of our work in sync. We must not send any of our dev branch work into the master branch because the master branch is the original source of truth and our dev branch must never break or change the master branch.
