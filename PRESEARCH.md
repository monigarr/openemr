# Clinical CoPilot — PreSearch (Week 2)

## Scope
Extend PRD 1 agent with multimodal ingestion + multi-agent orchestration + eval CI.

PRD 2 introduces a constrained, inspectable multi-agent layer built on top of PRD 1 orchestrator — not a replacement. :contentReference[oaicite:1]{index=1}

---

## Constraints
- Must reuse OpenEMR system
- OpenEMR source of truth documents: CONTRIBUTING.md READMEs CODE_OF_CONDUCT.md
- Client PRD_2_AgentForge_Clinical_CoPilot.md / PRD_2_AgentForge_Clinical_CoPilot.pdf
- No breaking changes
- Eval CI required

---

## Architecture Decisions

- Supervisor + 2 workers
- strict schemas
- hybrid RAG

---

## Risks
- hallucination / confabulation
- latency increase
- CI fragility
- extraction creates new data before verification (critical)

---

## Key Design Rule (NEW)

Extraction is treated as **untrusted input**, not truth, until:
- schema validated
- citation verified
- system-approved

---

## Strategy
- limit to 2 document types
- enforce schema validation
- enforce CI gate

---

## Observability (Updated)

- PRD 1: AgentTelemetry (LangFuse)
- PRD 2: adds LangChain

---

Final:
- LangChain = orchestration
- AgentTelemetry = observability authority

---

## Success
- grounded outputs
- CI catches regression