# Clinical CoPilot — AUDIT (Week 2 Addendum)

## New Risk Areas

### Document Processing
- OCR inaccuracies
- confabulated fields
- extracted data treated as truth (critical risk)

### Multi-Agent Complexity
- routing errors
- hidden reasoning
- supervisor becoming black box

### RAG Risks
- irrelevant evidence
- outdated guidelines

---

## New Mitigations

- extraction treated as untrusted input
- strict schema validation
- citation enforcement
- supervisor decisions logged
- reranking enforced
- verification extended to extracted data

---

## Compliance Notes
- documents = PHI
- no storage in logs
- no external leakage

---

## New Findings

| ID | Severity | Finding |
|----|----------|--------|
| W2-F1 | High | OCR hallucination risk |
| W2-F2 | High | missing citation in extraction |
| W2-F3 | Medium | RAG drift |
| W2-F2 | High | extraction treated as truth risk |
| W2-F4 | High | multi-agent opacity risk |

---

## Integration Impact

- extend verification layer to extracted data
- extend observability to multi-agent flows
- enforce auditability of routing decisions