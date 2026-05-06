<!--
  SPDX-License-Identifier: GPL-3.0-only

  README.md — Clinical Co-Pilot (oe-module-clinical-copilot) integrator documentation.

  Author: Monica Peters <monigarr@monigarr.com> GauntletAI.com
  Version: 0.1.0 | Last updated: 2026-05-03

  Usage: Copy module to interface/modules/custom_modules/oe-module-clinical-copilot/, enable in
  Admin → System → Modules, configure **Admin → Config** (Portal tab), open patient summary.

  Usage example: See "Install (OpenEMR Admin)" below; run PHPUnit filter ClinicalCopilot for verification tests.

  @see moduleConfig.php version.php openemr.bootstrap.php
-->
# Clinical Co-Pilot (`oe-module-clinical-copilot`)

AgentForge module: **multi-turn** co-pilot on the **patient summary** card using OpenAI **tool calling** (with **pre-merge fallback**), **citation verification**, **`ClinicalDomainRules`** safety pass, **session conversation** (no chart JSON in session), **structured telemetry**, and **PHPUnit-isolated** tests.

### Week 1 vs Week 2 (PRD 2)

- **Week 1 (baseline):** chart tools only (`get_chart_lists`, `get_recent_encounters`, `get_recent_labs`), verification over merged chart JSON.
- **Week 2 (multimodal + RAG):** **`upload_document`** stages a demo **PDF** for the session `pid`; the agent may call **`attach_and_extract`** (`lab_pdf` | `intake_form`), **`get_document_extractions`**, and **`retrieve_guidelines`** (`query`). Citations may use `document_extractions.*` and `guideline_evidence.chunks.*`. See repo-root **`W2_ARCHITECTURE.md`** and bundled **`resources/guidelines/corpus.json`**. Optional rerank: env **`CLINICAL_COPILOT_COHERE_API_KEY`**.

## Install (OpenEMR Admin)

1. Copy or merge this folder to `interface/modules/custom_modules/oe-module-clinical-copilot/`.
2. **Admin → System → Modules** → install **Clinical Co-Pilot (AgentForge)** → **Enable**.
3. Open a patient and go to the **patient summary / dashboard**; the **Clinical Co-Pilot** card should appear at the top of the primary column.

## Configuration

Registered on **Admin → Config** → **Portal** tab when this module loads (`Bootstrap::registerGlobals`). Same keys as `moduleConfig.php` metadata.

- **Enable:** **Clinical Co-Pilot: enable patient summary card** (`clinical_copilot_enable`, default on).
- **Model:** **Clinical Co-Pilot OpenAI model** (`clinical_copilot_openai_model`, default `gpt-4o-mini`).
- **OpenAI API key (pick one):**
  - **Admin → Config** → **Portal** tab → **Clinical Co-Pilot OpenAI API key** (password field), or
  - Environment: `CLINICAL_COPILOT_OPENAI_API_KEY` or `OPENAI_API_KEY` (Railway, Docker, etc.).
  - **Precedence:** If the Portal password field is non-empty, it wins over environment variables (avoids a stale `OPENAI_API_KEY` in a dev `.env` overriding a good Admin key).

**Week 2 optional persistence**

- **`CLINICAL_COPILOT_PERSIST_UPLOADS`**: set to `1` or `true` to store uploaded PDFs into OpenEMR **Documents** for the active patient (via `library/documents.php` `addNewDocument`). Requires full web bootstrap and appropriate ACL; failures do not block staging the temp file for extraction.
- **`CLINICAL_COPILOT_COHERE_API_KEY`**: optional guideline rerank (see Week 2 section above).

**Week 2 optional multimodal extraction (Gemini)**

- **`CLINICAL_COPILOT_EXTRACTION_PIPELINE`**: set to `gemini` to send PDFs (≤ 4 MB inline limit) to Google **Gemini** JSON extraction; requires **`CLINICAL_COPILOT_GEMINI_API_KEY`**. Optional **`CLINICAL_COPILOT_GEMINI_MODEL`** (default `gemini-1.5-flash`). Omit or use `stub` for deterministic demo extraction (default). Sending PHI to Google requires appropriate agreements.

Use **demo / synthetic data only** per course rules.

### Langfuse observability (optional)

**Off by default.** Requires **Admin → Config** → **Portal** tab → **Clinical Co-Pilot: allow Langfuse observability export** (`clinical_copilot_langfuse_enable`) **and** Langfuse API credentials in the web runtime environment.

| Variable | Purpose |
|----------|---------|
| `LANGFUSE_PUBLIC_KEY` | Langfuse project public key (Basic auth username) |
| `LANGFUSE_SECRET_KEY` | Langfuse secret key (Basic auth password) |
| `LANGFUSE_BASE_URL` | API host (default `https://hipaa.cloud.langfuse.com/`; use your region or self-hosted URL) |
| `LANGFUSE_ENABLED` | Set to `0` / `false` to disable export even if Globals allow (optional kill switch) |
| `LANGFUSE_CLINICAL_COPILOT_IO` | Omit or empty: **metadata-only** (model, tokens, latency, span names—no prompts/tool payloads). Set to `redacted` for **truncated** message previews (non-PHI / dev only unless legal approves). |
| `LANGFUSE_IO_MAX_CHARS` | Max length for redacted previews (default `500`) |
| `LANGFUSE_ID_SALT` | Optional server secret appended when hashing opaque `userId` / `sessionId` metadata (recommended if Langfuse is enabled) |

**Compliance:** Langfuse is a **third-party observability subprocessor** when enabled—same trust bar as other APM ([AUDIT.md](../../../../AUDIT.md) F1). Do **not** enable redacted I/O against real PHI without appropriate agreements or a **self-hosted** Langfuse deployment. Exports are **fail-open** (never break the co-pilot card).

## AJAX contract (`public/copilot_request.php`)

Same-origin **POST** with `csrf_token_form` (required). **Backward compatible:** posting only CSRF runs **`brief`** (initial briefing), same as before.

| Field | Required | Description |
|-------|----------|-------------|
| `csrf_token_form` | yes | Same as other OpenEMR forms |
| `action` | no | `brief` (default), `message`, `reset`, or **`upload_document`** (Week 2) |
| `user_message` | for `message` | Follow-up question (plain text) |
| `conversation_token` | for `message` | Opaque token from prior success response |
| `doc_type` | for `upload_document` | `lab_pdf` or `intake_form` |
| `clinical_copilot_file` | for `upload_document` | Multipart file field (`application/pdf`, demo) |

**Success JSON** (additive): existing keys `ok`, `text`, `usage`, `model`, `estimated_usd`, `request_id` plus **`conversation_token`**, **`messages`** (session transcript, text only), **`statements_for_ui`** (verified statements with PII-safe citation labels), optional **`fallback_premerged`**.

**UI / a11y:** Transcript uses `aria-live="polite"`; sources for the last reply render under **Sources (last reply)** with semantic lists; token usage is in a `<details>` block.

## Eval / tests

**PRD 2 golden gate (50 cases, no API calls):**

```bash
php interface/modules/custom_modules/oe-module-clinical-copilot/eval/run_eval.php
```

Regenerate `eval/cases.json` after changing `Prd2EvalRunner::buildDefaultCases()`:

```bash
php interface/modules/custom_modules/oe-module-clinical-copilot/eval/run_eval.php --export-cases
```

After intentional rubric expectation changes, refresh **`eval/prd2_eval_baseline.json`** (only when the golden run is fully green):

```bash
php interface/modules/custom_modules/oe-module-clinical-copilot/eval/run_eval.php --export-baseline
```

Guideline **dense manifest** (deterministic embedding checksums per chunk):

```bash
php interface/modules/custom_modules/oe-module-clinical-copilot/resources/guidelines/build_dense_manifest.php
```

Machine-readable summary (e.g. CI artifacts): `--summary-json` (still exits non-zero on failures or baseline regression).

Pre-commit (repo root): changing any file under this module runs the eval hook (`clinical-copilot-prd2-eval`).

**GitHub Actions:** `.github/workflows/clinical-copilot-prd2-eval.yml` runs the same eval and PHPUnit under `tests/Tests/Isolated/ClinicalCopilot/` (golden pass + **regression** `Prd2EvalGateRegressionIsolatedTest`) on pushes/PRs touching this module.

**Cost / latency (submission):** fill in repo-root `W2_COST_LATENCY_REPORT.md`.

```bash
composer dump-autoload -o
composer phpunit-isolated -- --filter ClinicalCopilot
```

(Or `vendor/bin/phpunit -c phpunit-isolated.xml --filter ClinicalCopilot` if Composer scripts are not used.)

## Architecture notes

- **Trust boundary:** Session `pid` only; AJAX ignores client-supplied patient id.
- **Tools:** `ToolRegistry` — `get_chart_lists`, `get_recent_encounters`, `get_recent_labs`; citations resolve under merged roots `chart_lists`, `recent_encounters`, `recent_labs`.
- **Verification:** `VerificationGate` then `ClinicalDomainRules`; uncited or unsafe lines are stripped or withheld.
- **Telemetry:** Monolog JSON: steps, per-round OpenAI, `tool:name`, token totals, rough USD; optional **Langfuse** trace UI when Globals + keys allow (see above).

See repo root `ARCHITECTURE.md` and `USERS.md` for full AgentForge context.
