# Week 2 — Cost and latency report

Replace bracketed items and illustrative numbers with **your** measured spend and timings before final submission.

## Scope

- **Dev environment:** Docker `docker/development-easy` (see `CONTRIBUTING.md`); app URL typically `http://localhost:8300/`.
- **Model routing:** `.cursor/rules/PRD-2-AgentForge-Agent-Roster.mdc` (Lead / Supervisor / SubAgents).
- **Module:** `interface/modules/custom_modules/oe-module-clinical-copilot/`.

## How to measure (before filling tables)

1. **Spend:** Export usage from each provider dashboard for the sprint window (OpenAI, Google AI if Gemini extraction on, Cohere if rerank on, Langfuse if hosted). Do not paste raw prompts or PHI into tickets.
2. **Latency:** Wrap the copilot request path with timestamps or use Langfuse spans; label steps (`tool_round`, `retrieve_guidelines`, `attach_and_extract`) without patient identifiers.
3. **Projection:** Multiply measured tokens × your **contract** price per 1M tokens; add fixed per-call fees (rerank, extraction) from vendor docs.

## Actual development spend (approximate)

| Item | Amount | Notes |
|------|--------|-------|
| Cursor Ultra | $214 Month, $62.00 Usage | PRD 1 & 2 |
| ChatGPT Pro | $150.00 Month | PRD 1 & 2  |
| OpenAI API | $0.02| Doc format / plan / idea / advise / alignment / presentation scripts |
| Gemini (extraction) | `[TBD]` | Only if `CLINICAL_COPILOT_EXTRACTION_PIPELINE=gemini` |
| Cohere rerank | `[TBD]` | Only if `CLINICAL_COPILOT_COHERE_API_KEY` set |
| Langfuse HIPAA | $30 | Self-host vs cloud |
| LangSmith | $ | observability |
| Railway Pro | $20 Month,  Usage $23.18 | staging deployment |
| Vercel | $ | prod deployment | 
| CloudClusters.io | $15 | requested refund |
| **Total dev (approx., excl. refunds)** | **~$514** | Sum of table rows above where numeric (Cursor+ChatGPT+API+Langfuse+Railway+CloudClusters); verify in finance export before submission |

## Projected production cost (per 100 copilot encounters)

**Illustrative math only** — plug in your real token histogram and negotiated rates.

Assumptions for the example row: 4 tool rounds per encounter; 500 prompt + 300 completion tokens per round; chat-class model priced at **$0.15 / 1M input** and **$0.60 / 1M output** (verify against current list/contract pricing).

- Input tokens / 100 encounters: `100 × 4 × 500` = 200k → **$0.03**
- Output tokens / 100 encounters: `100 × 4 × 300` = 120k → **$0.072**
- **Chat subtotal (illustrative):** ~**$0.10 / 100 encounters**

| Component | Assumption | Est. USD / 100 encounters |
|-----------|------------|---------------------------|
| Chat + tools | As above (replace with your mix) | ~0.10 (illustrative) |
| Extraction | e.g. 10 uploads / 100 encounters × cost per PDF call | `[TBD]` |
| Rerank | e.g. 1 rerank / encounter × Cohere unit price | `[TBD]` |
| **Total** | | `[TBD]` |

## Latency (p50 / p95)

Fill from logs or Langfuse after a representative run (synthetic patients only in shared environments).

| Step | p50 (ms) | p95 (ms) | Notes |
|------|----------|----------|-------|
| `tools_collect_merged_base` | `[TBD]` | `[TBD]` | Merged tool JSON build |
| OpenAI tool rounds | `[TBD]` | `[TBD]` | Often dominant |
| `retrieve_guidelines` | `[TBD]` | `[TBD]` | Hybrid sparse + dense (`DeterministicDenseEmbedder`) + optional Cohere rerank |
| `attach_and_extract` | `[TBD]` | `[TBD]` | Stub fast; Gemini/network varies |
| Verification + domain rules | `[TBD]` | `[TBD]` | In-process |
| **End-to-end (brief)** | `[TBD]` | `[TBD]` | No upload |
| **End-to-end (with upload + extract)** | `[TBD]` | `[TBD]` | PDF size matters |

## Bottleneck analysis

1. `[TBD]` — e.g. sequential OpenAI tool rounds vs parallelizable retrieval.
2. `[TBD]` — e.g. PDF inline size limit (~4 MB) and cold API latency for Gemini.

## Eval CI

- Local / pre-commit: `clinical-copilot-prd2-eval`
- GitHub Actions: `.github/workflows/clinical-copilot-prd2-eval.yml`
- Command: `php interface/modules/custom_modules/oe-module-clinical-copilot/eval/run_eval.php`
- Summary JSON: same command with `--summary-json` (artifact in CI).
- **Regression proof:** `tests/Tests/Isolated/ClinicalCopilot/Prd2EvalGateRegressionIsolatedTest.php` asserts the gate fails on an injected bad citation and on wrong case count.
- **Per-rubric baseline:** `eval/prd2_eval_baseline.json` compared each run (95% floor + 5 pp max drop vs baseline); see `Prd2EvalBaselineChecker`.

## Submission placeholders (PRD checklist)

| Deliverable | Value |
|-------------|--------|
| **Deployed application URL** | `[REPLACE_ME — e.g. Railway staging URL from USERS.md / team runbook]` |
| **Demo video (3–5 min)** | `[REPLACE_ME — Loom/YouTube unlisted; show upload, extract, guidelines, citations, eval CI, optional Langfuse]` |
| **Cost/latency last updated** | `2026-05-05` |

## Document control

- Version: 0.2.0 (measurement guide + placeholders)
- Date: 2026-05-06
