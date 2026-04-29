# Clinical Co-Pilot (`oe-module-clinical-copilot`)

AgentForge Week 1 module: **inter-visit briefing** on the **patient summary** (primary dashboard column) using OpenAI, **citation-backed** statements, **structured telemetry**, and **PHPUnit-isolated** verification tests.

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

## Eval / tests

```bash
composer dump-autoload -o
composer phpunit-isolated -- --filter ClinicalCopilot
```

## Architecture notes

- **Trust boundary:** Session `pid` only; AJAX ignores client-supplied patient id.
- **Verification:** Model must return JSON `statements` with `citations` as dot paths into chart tool JSON; uncited lines are stripped server-side.
- **Telemetry:** Monolog channel with JSON payload: steps, token counts, rough USD estimate (not a bill).

See repo root `ARCHITECTURE.md` and `USERS.md` for full AgentForge context.
