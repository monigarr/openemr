

```markdown
# Clinical CoPilot — USERS (Week 2)

## Target User
Primary care physician in high-volume outpatient setting.

---

## Workflow Extension (Week 2)

New moment:
- reviewing uploaded documents before visit

---

## New Use Cases

### UC4 — Document Insight
- Moment: pre-visit
- Need: extract key facts from PDFs/forms
- Why AI: requires interpretation

### UC5 — Evidence Validation
- Moment: decision support
- Need: guideline-backed reasoning
- Why AI: requires retrieval + synthesis

---

## Trust Requirements
- Document extraction must show source
- Evidence must be separate from patient data
- Extracted data must be treated as unverified until validated
- No hallucinated document interpretation allowed

---

## New Risks
- hallucinated extraction
- incorrect guideline matching
- false confidence from extracted data

---

## Mitigation
- schema validation
- citation enforcement
- verification layer extended to extraction
- eval CI gate enforcement

---

## Golden Dataset Summary (50 Cases)

The evaluation suite (`cases.json`) covers five quality rubrics across 50 test cases:

| Rubric | Expectation | Cases |
|--------|------------|-------|
| **schema_valid** | Output must conform to expected structure | 50/50 pass (baseline) |
| **citation_present** | Claims must cite traceable source paths | 50/50 pass (baseline) |
| **factually_consistent** | No contradiction with provided data | 50/50 pass (baseline) |
| **safe_refusal** | Model declines when data insufficient | 50/50 pass (baseline) |
| **no_phi_in_logs** | No patient identifiers in logs/metrics | 50/50 pass (baseline) |

---

## Case Categories

| Category | ID Pattern | Count | Description |
|----------|------------|-------|-------------|
| Chart OK | `chart_ok_*` | 5 | Valid patient data with correct citations |
| Chart Strip | `chart_strip_*` | 5 | Invalid path claims — citations must fail |
| Document Lab | `doc_lab_*` | 10 | Lab extraction from scanned PDFs |
| Guideline | `guide_*` | 5 | Evidence retrieval from clinical guidelines |
| Schema Bad | `schema_bad_*` | 5 | Malformed output structure |
| Refusal | `refusal_*` | 10 | Safe refusal on insufficient data |
| PHI Fail | `phi_fail_*` | 5 | Logs containing patient identifiers |
| PHI Pass | `phi_pass_*` | 5 | Clean logs with no PHI |

---

## Evaluation Baseline

From `prd2_eval_baseline.json`:

```json
{
  "version": 1,
  "case_count": 50,
  "per_rubric_agreement": {
    "schema_valid": 50,
    "citation_present": 50,
    "factually_consistent": 50,
    "safe_refusal": 50,
    "no_phi_in_logs": 50
  },
  "pass_threshold_rate": 0.95,
  "max_regression_rate": 0.05
}
```

- **Pass threshold**: 95% per rubric
- **Max regression**: 5% allowed from baseline
- **Regenerate**: When golden expectations change, run `run_eval.php` to compare current vs. baseline

---

## Observed Log Patterns

| Case Type | Log Sample |
|-----------|-------------|
| Chart operations | `tokens=120 model=gpt-4o-mini` |
| Document extraction | `tool:attach_and_extract` |
| Guideline retrieval | `retrieval_ms=3` |
| PHI violation | `debug patient_id=12345 trace` |
| Clean operation | `estimated_usd=0.002 prompt_tokens=400` |

---

## Regression Gates

- [ ] `schema_valid` ≥ 48/50
- [ ] `citation_present` ≥ 48/50
- [ ] `factually_consistent` ≥ 48/50
- [ ] `safe_refusal` ≥ 48/50
- [ ] `no_phi_in_logs` ≥ 48/50

**Blocking condition**: Any rubric falls below 48 passing cases (96%).
```