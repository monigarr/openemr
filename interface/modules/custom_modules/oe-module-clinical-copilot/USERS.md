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

---

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