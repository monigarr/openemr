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

## Troubleshooting: `oe-module-clinical-copilot` not in Manage Modules

OpenEMR lists **unregistered** custom modules by scanning the directory  
`/var/www/localhost/htdocs/openemr/interface/modules/custom_modules/` on the running container. If **Clinical Co-Pilot (AgentForge)** does not appear under **Administration → System → Modules → Manage Modules**, the folder is missing or unreadable **at runtime** (wrong branch built, volume shadowing, or you still need Register / Install / Enable).

### Step 1: Confirm files on the running container

The path `/var/www/localhost/htdocs/openemr/...` exists **only inside the Linux container** that runs OpenEMR on Railway. It does **not** exist on your Windows or Mac host.

**Do not use** `railway run bash` or `railway shell` for this check: both run **on your machine** and only inject Railway environment variables. `ls` there will correctly report “No such file or directory” for `/var/www/...`.

**Do use** one of these so commands run **inside** the deployed service:

- **CLI (recommended):** from your linked project directory, run `railway ssh` to open a shell in the service container, then run the commands below. One-shot: `railway ssh -- ls -la /var/www/localhost/htdocs/openemr/interface/modules/custom_modules/`
- **Dashboard:** **service → … → Shell** (same idea: session in the container, not your laptop).

Then:

```bash
ls -la /var/www/localhost/htdocs/openemr/interface/modules/custom_modules/
ls -la /var/www/localhost/htdocs/openemr/interface/modules/custom_modules/oe-module-clinical-copilot/ 2>/dev/null | head
stat -c '%U:%G %a %n' /var/www/localhost/htdocs/openemr/interface/modules/custom_modules/oe-module-clinical-copilot 2>/dev/null
mount | grep htdocs
```

On Alpine-based images, if `stat -c` is not available, `ls -la` on those paths is enough.

Interpretation:

| Result | Likely cause |
|--------|----------------|
| Folder missing; **no** other `oe-module-*` dirs | **Wrong Git branch** for the Railway build, or stale build cache — see below. |
| Folder missing; **other** `oe-module-*` dirs exist | **Volume** mounted over the app tree hiding new image layers — see below. |
| Folder present with `info.txt`, `moduleConfig.php`, `openemr.bootstrap.php`, `src/` | Files are OK — use **Refresh Modules** then **Register → Install → Enable** in Manage Modules. |

### Cause A: Wrong branch or stale build cache

The Dockerfile **does not** `git checkout` a branch; Railway builds whatever branch is connected under **Settings → Source → Branch**. Set it to **`prd_1_agentforge_monigarr`** (or the branch that contains the module). Confirm **Settings → Build → Dockerfile path** is `docker/agentforge-railway/Dockerfile`. Redeploy **without build cache**, then repeat Step 1.

### Cause B: Volume shadowing the application tree

Persist only **`sites/`** (and your DB service). If a Railway volume is mounted at `/var/www/localhost/htdocs/openemr` or `/var/www/localhost/htdocs/`, it replaces the image filesystem at that path and **hides** `interface/modules/custom_modules/oe-module-clinical-copilot` from new deploys. Remount the volume to **`.../openemr/sites`** only, redeploy, repeat Step 1.

### Cause C: Register and enable in OpenEMR

Files in the image do **not** auto-enable the module. After Step 1 shows the folder:

1. **Administration → System → Modules → Manage Modules**
2. **Refresh Modules**
3. Under **Unregistered**, find **Clinical Co-Pilot (AgentForge)** (from `info.txt` line 1) → **Register**
4. **Install**, then **Enable**
5. Open a **patient summary**; the card is wired when `mod_active = 1` loads `openemr.bootstrap.php`.

If the module was **Registered** earlier but files were missing on a prior deploy, OpenEMR may have set `mod_active = 0` when bootstrap was unreadable. After files are fixed, use **Enable** again on the Registered row.

### Runtime configuration (after enable)

- **Administration → Globals → Portal** → **Clinical Co-Pilot OpenAI API key**, and/or  
- Railway **Variables**: `CLINICAL_COPILOT_OPENAI_API_KEY` or `OPENAI_API_KEY`  
- Confirm globals **`clinical_copilot_enable`** = on (default in `moduleConfig.php`).

### Verify locally after `docker build` (optional)

From repo root, after `docker build -f docker/agentforge-railway/Dockerfile -t openemr:agentforge .`:

```shell
docker run --rm openemr:agentforge ls -la /var/www/localhost/htdocs/openemr/interface/modules/custom_modules/oe-module-clinical-copilot/
```

You should see `info.txt`, `moduleConfig.php`, `openemr.bootstrap.php`, and `src/`.

## Security

Change default database and OpenEMR admin passwords before any real data. Do not commit secrets.

## Related

- Generic “build from clone” without a branch-specific path: [docker/from-source/README.md](../from-source/README.md).
