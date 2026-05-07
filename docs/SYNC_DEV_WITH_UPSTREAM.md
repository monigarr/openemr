# Sync your dev branch with upstream (keep `master` clean)

This document is for **everyone working on this fork**: keep feature and integration branches current with upstream OpenEMR **without** putting fork-specific work on **`master`**.

**Cursor users:** the agent skill `sync-dev-with-upstream` lives at [.cursor/skills/sync-dev-with-upstream/SKILL.md](../.cursor/skills/sync-dev-with-upstream/SKILL.md); it points here so there is a single copy of the full procedure.

## Policy (verbatim)

Merge the latest changes from the upstream repository into our current dev branch so that we can keep all of our work in sync. We must not send any of our dev branch work into the master branch because the master branch is the original source of truth and our dev branch must never break or change the master branch.

## What this fork expects

- **`master`** tracks **[openemr/openemr](https://github.com/openemr/openemr)** (or your team’s canonical remote). Treat it as an **upstream mirror**, not your day-to-day integration branch for fork-only features.
- **Feature work** lives on branches such as `prd_1_agentforge_monigarr`, `prd2_agentforge`, or short topic branches cut from those—see `.cursor/rules/` for branch naming if unsure.
- **Branch names:** If the default branch were ever `main` instead of `master`, substitute `main` / `upstream/main` in every command below. The rules stay the same.

## Invariants (do not violate)

1. The branch that mirrors upstream (`master` here) must only receive commits that come **from upstream** (fetch / merge / fast-forward), not exclusive dev-branch work.
2. **Never merge your dev branch into `master`** to “sync.” Direction is always **upstream → `master` → dev** (or **upstream → dev**), never the reverse for your WIP.
3. **Do not push dev-only history to `master`** on `origin` as part of normal work. Publish work on your dev branch and open PRs with the correct base.
4. **Resolve merge conflicts on the dev branch**, not on `master`.

## Prerequisites

```bash
git remote -v
```

- **`upstream`** should point at the canonical OpenEMR repo, for example:
  - `https://github.com/openemr/openemr.git`
- **`origin`** is usually your fork.
- If `upstream` is missing:
  ```bash
  git remote add upstream https://github.com/openemr/openemr.git
  ```

## Recommended workflow (merge)

Replace `my-dev-branch` with your actual branch name.

### 1. Fetch

```bash
git fetch upstream
git fetch origin
```

### 2. Update local `master` from upstream only

```bash
git checkout master
git merge --ff-only upstream/master
```

This updates **local** `master` only; it does not move your dev commits onto `master`.

If `--ff-only` fails, local `master` has diverged. If your policy is a **pure mirror** (no intentional commits on `master` that are not from upstream), you can realign:

```bash
git checkout master
git reset --hard upstream/master
```

**Do not** use `reset --hard` if `master` has commits you must keep—stop and decide with your team before rewriting.

### 3. Merge into your dev branch

```bash
git checkout my-dev-branch
git merge master
```

Equivalent after a fresh `master`: `git merge upstream/master` on the dev branch.

### 4. Sanity-check history

- `git log master..HEAD` — should list **your** fork’s commits on top of `master`.
- `git log HEAD..master` — should be **empty** if your branch fully contains `master`.

### 5. Push the dev branch only

```bash
git push origin my-dev-branch
```

Do **not** run `git push origin master` as part of routine feature sync unless you are deliberately publishing a **fast-forward-only** update of the fork mirror and your workflow allows it.

## Checklist

- [ ] `git fetch upstream` (and `origin` if needed).
- [ ] Local **`master`** updated from **`upstream/master`** (`--ff-only` or policy-approved reset).
- [ ] Checked out **dev branch**; merged **`master`** (or `upstream/master`) **into dev**.
- [ ] Conflicts fixed **on dev**; tests run if your team requires them.
- [ ] Pushed **`my-dev-branch`** only.

## Conflicts

1. Stay on the **dev** branch for the merge.
2. Edit files, `git add`, then `git merge --continue` (or complete the merge with a commit if Git stopped).
3. Do **not** fix conflicts by committing on `master`.

## Anti-patterns

| Wrong | Why |
|--------|-----|
| `git checkout master && git merge my-dev-branch` | Lands dev work on the mirror branch. |
| `git push origin master` after merging dev locally | Can publish dev history as `master`. |
| Rebasing `master` onto dev | Upstream history must not be rewritten from dev. |

## Optional: rebase on dev

Some teams use `git pull --rebase upstream master` while on the dev branch. Same rule: **never** rebase `master` onto dev; only rebase **dev** onto newer upstream/`master` if your team agrees.
