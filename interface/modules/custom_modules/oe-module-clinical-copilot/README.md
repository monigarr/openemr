<!--
  SPDX-License-Identifier: GPL-3.0-only

  README.md — Clinical Co-Pilot (oe-module-clinical-copilot) integrator documentation.

  Author: Monica Peters <monigarr@monigarr.com> GauntletAI.com
  Version: 0.1.0 | Last updated: 2026-04-30

  Usage: Copy module to interface/modules/custom_modules/oe-module-clinical-copilot/, enable in
  Administration → System → Modules, configure globals and OpenAI API key, open patient summary.

  Usage example: See "Install (OpenEMR Admin)" below; run PHPUnit filter ClinicalCopilot for verification tests.

  @see moduleConfig.php version.php openemr.bootstrap.php
-->
# Clinical Co-Pilot (`oe-module-clinical-copilot`)

AgentForge module: **multi-turn** co-pilot on the **patient summary** card using OpenAI **tool calling** (with **pre-merge fallback**), **citation verification**, **`ClinicalDomainRules`** safety pass, **session conversation** (no chart JSON in session), **structured telemetry**, and **PHPUnit-isolated** tests.

## Install (OpenEMR Admin)

1. Copy or merge this folder to `interface/modules/custom_modules/oe-module-clinical-copilot/`.
2. **Administration → System → Modules** → install **Clinical Co-Pilot (AgentForge)** → **Enable**.
3. Open a patient and go to the **patient summary / dashboard**; the **Clinical Co-Pilot** card should appear at the top of the primary column.

## Configuration

- **Enable:** Globals metadata from `moduleConfig.php` — `clinical_copilot_enable` (default on).
- **Model:** `clinical_copilot_openai_model` (default `gpt-4o-mini`).
- **OpenAI API key (pick one):**
  - Environment: `CLINICAL_COPILOT_OPENAI_API_KEY` or `OPENAI_API_KEY` (preferred on [Cloud Clusters](https://www.cloudclusters.io/) and Docker), or
  - **Administration → Globals → Portal** → **Clinical Co-Pilot OpenAI API key** (password field).

Use **demo / synthetic data only** per course rules.

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
- **Telemetry:** Monolog JSON: steps, per-round OpenAI, `tool:name`, token totals, rough USD.

See repo root `ARCHITECTURE.md` and `USERS.md` for full AgentForge context.
