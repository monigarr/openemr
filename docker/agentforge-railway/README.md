# OpenEMR Docker image (custom branch / Railway)

This directory is **only** for building an image from **your** checkout so custom code (for example under `interface/modules/custom_modules/`) is included. It does not replace or modify the community `docker/from-source` or `docker/production` definitions.

## Layout

| File | Purpose |
|------|---------|
| [`Dockerfile`](Dockerfile) | **Default for Railway:** multi-stage production image aligned with [openemr-devops `docker/openemr/8.1.1`](https://github.com/openemr/openemr-devops/tree/master/docker/openemr/8.1.1). App tree is `COPY` from the repo root at build time; `php.ini`, `openemr.conf`, `openemr.sh`, `ssl.sh`, upgrades, and utilities are **vendored** under [`upstream/docker/openemr/8.1.1/`](upstream/docker/openemr/8.1.1/) (see [`upstream/UPSTREAM.md`](upstream/UPSTREAM.md)). **No** flex runtime git clone. |
| [`Dockerfile.flex`](Dockerfile.flex) | Previous **flex**-based image (`FROM openemr/openemr:flex`) if you need that behavior. Point Railway’s Dockerfile path here to use it. |
| [`env.langfuse.example`](env.langfuse.example) | Placeholder-only list of Langfuse-related variable **names** for copying into Railway Variables or a local `.env` (no secrets committed). |

## Branch (`prd2_agentforge` and `upstream/`)

**Application code:** Railway and local builds use whatever is in the Git **build context**. Use branch **`prd2_agentforge`** (PRD 2 Clinical Co-Pilot) so `interface/modules/custom_modules/oe-module-clinical-copilot/` and the rest of the tree match what you ship. The Dockerfile does **not** run `git checkout`.

**Vendored devops slice:** Runtime scripts, `php.ini`, Apache config, and upgrade helpers come from **`docker/agentforge-railway/upstream/docker/openemr/8.1.1/`** (mirrors [openemr-devops](https://github.com/openemr/openemr-devops) — see [`upstream/UPSTREAM.md`](upstream/UPSTREAM.md)). That tree must stay **committed** in the repo; the image build does not download it from GitHub.

**`master`:** Keep your fork’s **`master`** aligned with `openemr/openemr:master`; merge **`master` → `prd2_agentforge`** for compatibility. Do not merge feature work into **`master`** just to deploy.

## Build locally (from repository root)

```shell
git checkout prd2_agentforge
docker build -f docker/agentforge-railway/Dockerfile -t openemr:agentforge .
```

Optional build args:

```shell
docker build -f docker/agentforge-railway/Dockerfile -t openemr:agentforge \
  --build-arg GIT_COMMIT="$(git rev-parse HEAD 2>/dev/null || echo unknown)" \
  --build-arg SOURCE_BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo prd2_agentforge)" \
  --build-arg OPENEMR_DEVOPS_DIR=8.1.1 \
  .
```

- **`OPENEMR_DEVOPS_DIR`:** Name of the directory under [`upstream/docker/openemr/`](upstream/docker/openemr/) (default `8.1.1`). Change this only if you vendor a different devops version folder (and copy files accordingly). To pick up upstream script or `php.ini` changes, refresh the vendored tree per [`upstream/UPSTREAM.md`](upstream/UPSTREAM.md).

## Railway.com

1. Create a service from your GitHub repo.
2. Set the deployment **branch** to **`prd2_agentforge`** (or the branch that contains your module).
3. Set **root directory** to the repository root (leave empty if the whole repo is the service root).
4. Set **Dockerfile path** to `docker/agentforge-railway/Dockerfile` (or `docker/agentforge-railway/Dockerfile.flex` for the flex variant).
5. Use a **MySQL or MariaDB** plugin (or second service) and set OpenEMR database env vars the same way as [docker/production/docker-compose.yml](../production/docker-compose.yml) (`MYSQL_HOST`, `MYSQL_ROOT_PASS`, etc.). OpenEMR’s Docker entrypoint expects those variables.
6. **Confirm the production Dockerfile is what Railway built:** In **Build** logs you should see stages such as `[base …]`, `[openemr-source]`, `[openemr-composer]`, `[openemr-assets]`, `[production …]`. You should **not** see `FROM openemr/openemr:flex` or a single combined `RUN` that runs `npm ci` immediately after `composer install` in the flex style. If you still see **“Configuring a new flex openemr docker”** or **git clone** of `github.com/openemr/openemr` in **Deploy** logs, the running image is still flex: fix **Git branch** (contains the production `Dockerfile`), **Dockerfile path** (`docker/agentforge-railway/Dockerfile`), and redeploy **without build cache** so Railway does not reuse an old image.
7. **HTTP / `PORT`:** Railway’s edge forwards to the port in the service’s [`PORT` variable](https://docs.railway.com/guides/public-networking). Apache listens on **80** (and **443** for TLS). This image sets `ENV PORT=80`. If you still see **502**, set an explicit Railway variable **`PORT=80`**. First boot may take a few minutes until logs show **Starting apache!** while the DB and auto-setup run.
8. **Persistence:** Mount or provision volumes for `sites/` and database data for anything beyond a demo.

### Flex variant only (`Dockerfile.flex`)

If you use **Dockerfile.flex**, flex may clone upstream OpenEMR at container start unless you rely on image-only content; see flex docs on Docker Hub. Optional variables: `FLEX_REPOSITORY`, `FLEX_REPOSITORY_BRANCH` / `FLEX_REPOSITORY_TAG`. Langfuse variables below apply the same way: PHP reads the container environment at runtime.

### Langfuse (Clinical Co-Pilot)

Optional observability for the **Clinical Co-Pilot** module uses `getenv()` in PHP; the Docker image does **not** bake Langfuse settings and does **not** copy a repo `.env` file into the image.

**Railway:** If your keys live only in a **gitignored** `.env` on your laptop, they are **not** sent to Railway on Git push. Add the same variables under **your OpenEMR service → Variables** (or `railway variables` / shared secrets). They must be on the **web** service that runs this image, not only on a database plugin.

**OpenEMR Admin:** Enable **Administration → Globals → Config → Portal** → **Clinical Co-Pilot: allow Langfuse observability export** (`clinical_copilot_langfuse_enable`). Without this toggle, export stays off even when keys are set.

**HIPAA Langfuse cloud:** Set **`LANGFUSE_BASE_URL=https://hipaa.cloud.langfuse.com`** (or **`LANGFUSE_HOST`** to the same value). If both are unset, the application defaults to `https://cloud.langfuse.com`, which is the wrong host for HIPAA-region project keys.

| Variable | Purpose |
|----------|---------|
| `LANGFUSE_PUBLIC_KEY` | Project public key (HTTP Basic username) |
| `LANGFUSE_SECRET_KEY` | Secret key (HTTP Basic password) |
| `LANGFUSE_BASE_URL` | API base URL (preferred). Example: `https://hipaa.cloud.langfuse.com` |
| `LANGFUSE_HOST` | Alternative to `LANGFUSE_BASE_URL` when only the host is set |
| `LANGFUSE_ENABLED` | Optional: `0` / `false` to disable export even if Globals allow |
| `LANGFUSE_RELEASE` | Optional trace `release` label (e.g. Git commit or deploy id) |
| `LANGFUSE_TRACING_ENVIRONMENT` | Optional trace environment (falls back to `LANGFUSE_ENV`) |
| `LANGFUSE_ENV` | Optional trace environment if `LANGFUSE_TRACING_ENVIRONMENT` is unset |
| `LANGFUSE_CLINICAL_COPILOT_IO` | Omit or empty: metadata-only generations. `redacted`: truncated previews (compliance-sensitive) |
| `LANGFUSE_IO_MAX_CHARS` | Max length for redacted previews (default `500`) |
| `LANGFUSE_ID_SALT` | Optional secret for hashing opaque `userId` / `sessionId` in traces |

See also [`interface/modules/custom_modules/oe-module-clinical-copilot/README.md`](../../interface/modules/custom_modules/oe-module-clinical-copilot/README.md) and the template [`env.langfuse.example`](env.langfuse.example).

**Local smoke test with `.env`:** from repo root, after `docker build`, pass variables into the container explicitly (the image never loads `.env` by itself):

```shell
docker run --rm --env-file .env openemr:agentforge sh -c "env | grep LANGFUSE"
```

**PRD2 Modernized (Next.js):** If you deploy the patient dashboard as a separate **Node** service, set the same `LANGFUSE_*` variables there and add **`DASHBOARD_LANGFUSE_ENABLE=1`** to emit optional FHIR-proxy spans (see `Documentation/ARCHITECTURE_PRD2_MODERNIZED.md` §11). No OpenEMR Portal toggle applies to the Node app.

**Troubleshooting:** If traces never appear, confirm Railway Variables on the OpenEMR service (Track A), the Portal global toggle for the module, and the correct Langfuse host for your project; for Track B, confirm variables on the frontend service and `DASHBOARD_LANGFUSE_ENABLE`. Check deploy/application logs for Langfuse flush or HTTP errors; ingestion is fail-open and does not block the co-pilot UI or dashboard.

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
|--------|--------------|
| Folder missing; **no** other `oe-module-*` dirs | **Wrong Git branch** for the Railway build, or stale build cache — see below. |
| Folder missing; **other** `oe-module-*` dirs exist | **Volume** mounted over the app tree hiding new image layers — see below. |
| Folder present with `info.txt`, `moduleConfig.php`, `openemr.bootstrap.php`, `src/` | Files are OK — use **Refresh Modules** then **Register → Install → Enable** in Manage Modules. |

### Cause A: Wrong branch or stale build cache

The Dockerfile **does not** `git checkout` a branch; Railway builds whatever branch is connected under **Settings → Source → Branch**. Set it to **`prd2_agentforge`** (or the branch that contains the module). Confirm **Settings → Build → Dockerfile path** is `docker/agentforge-railway/Dockerfile`. Redeploy **without build cache**, then repeat Step 1.

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
