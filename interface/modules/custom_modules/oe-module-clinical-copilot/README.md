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

## Install (OpenEMR Admin)

1. Copy or merge this folder to `interface/modules/custom_modules/oe-module-clinical-copilot/`.
2. **Admin → System → Modules** → install **Clinical Co-Pilot (AgentForge)** → **Enable**.
3. Open a patient and go to the **patient summary / dashboard**; the **Clinical Co-Pilot** card should appear at the top of the primary column.

## Configuration

Registered on **Admin → Config** → **Portal** tab when this module loads (`Bootstrap::registerGlobals`). Same keys as `moduleConfig.php` metadata.

- **Enable:** **Clinical Co-Pilot: enable patient summary card** (`clinical_copilot_enable`, default on).
- **Model:** **Clinical Co-Pilot OpenAI model** (`clinical_copilot_openai_model`, default `gpt-4o-mini`).
- **OpenAI API key (pick one):**
  - Environment: `CLINICAL_COPILOT_OPENAI_API_KEY` or `OPENAI_API_KEY` (railway and Docker), or
  - **Admin → Config** → **Portal** tab → **Clinical Co-Pilot OpenAI API key** (password field).

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
| `action` | no | `brief` (default), `message`, or `reset` |
| `user_message` | for `message` | Follow-up question (plain text) |
| `conversation_token` | for `message` | Opaque token from prior success response |

**Success JSON** (additive): existing keys `ok`, `text`, `usage`, `model`, `estimated_usd`, `request_id` plus **`conversation_token`**, **`messages`** (session transcript, text only), **`statements_for_ui`** (verified statements with PII-safe citation labels), optional **`fallback_premerged`**.

**UI / a11y:** Transcript uses `aria-live="polite"`; sources for the last reply render under **Sources (last reply)** with semantic lists; token usage is in a `<details>` block.

## Eval / tests

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
