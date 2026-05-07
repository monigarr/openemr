# Clinical CoPilot — Week 2 ARCHITECTURE

## 0. Executive Summary

Week 2 extends the PRD 1 Clinical CoPilot into a multimodal, multi-agent system capable of ingesting clinical documents, extracting structured facts, retrieving guideline evidence, and producing fully grounded outputs.

This is an **extension of PRD 1 — not a replacement**.

The system preserves PRD 1 guarantees:
- Verification-first output
- Bounded tool access
- Session-based trust model
- Full observability via AgentTelemetry

New capabilities:
- Multimodal document ingestion (lab PDF, intake form)
- Supervisor + worker multi-agent routing
- Eval-driven CI gating

---

## 1. Architecture Position

- OpenEMR remains system of record
- PRD 1 orchestrator remains core authority
- PRD 2 multi-agent layer is an extension on top
- AI remains untrusted reasoning layer
- Verification remains mandatory gate
- Eval CI is production gate

---

## 2. Design Principles (M.O.M / M.I.L.E)

- Intelligence-led extraction (never blind OCR trust)
- Extraction is treated as **untrusted input**
- Minimal expansion of surface area
- Brownfield-safe (no OpenEMR core modification)
- Observability-first (trace everything)
- Eval-driven development (CI enforced)

---

## 3. System Overview

User → OpenEMR UI → Controller  
→ PRD1 Orchestrator Core  
→ Supervisor Agent (extension)  
→ Worker 1 (Extractor)  
→ Worker 2 (Evidence Retriever)  
→ Verification Gate  
→ UI Response  

---

## 4. Multi-Agent Position (Critical Clarification)

PRD 2 multi-agent architecture is:

> A constrained, inspectable routing layer built on top of the PRD 1 orchestrator — not a replacement.

- Supervisor = routing logic only
- Workers = bounded responsibilities
- All actions remain observable and verifiable

No agent has:
- direct DB access
- write authority
- uncontrolled autonomy

---

## 5. Tool Layer (Updated)

| Tool | Role | Boundary |
|------|------|----------|
| attach_and_extract | document ingestion | server-controlled |
| retrieve_guidelines | RAG retrieval | read-only |
| get_patient_context | OpenEMR data | bounded |

### Write Boundary Clarification

`attach_and_extract`:
- executes server-side only
- persists data through controlled pipelines
- model does NOT write to DB
- model cannot modify records

---

## 6. Extraction Model (Critical Update)

Extraction pipeline:

Document → VLM → Structured JSON → Validation → Verification → Persist

### Rule:
> Extraction output is **not truth**. It is untrusted input until:
- schema validated
- citation linked to source
- verified by system

---

## 7. Verification (Extended)

Verification now applies to:
1. OpenEMR structured data
2. Extracted document data
3. Retrieved guideline evidence

Requirements:
- every claim must have citation
- patient facts ≠ guideline evidence
- unsupported claims removed

---

## 8. Observability (Reconciled)

PRD 1:
- AgentTelemetry (optional **Langfuse** export when configured)

PRD 2:
- **LangChain** for orchestration is **planned**; it is **not yet in tree** in this repository’s module code (current routing uses the OpenAI tool loop in `AgentOrchestrator`).

### Final Position:

> When adopted, LangChain is orchestration; **AgentTelemetry** remains the system of record for observability today.

All logs must include:
- tool sequence
- latency per step
- token usage
- cost estimate
- eval result

No PHI in logs

---

## 9. Eval Strategy (Critical)

- 50-case golden dataset
- boolean rubrics only
- CI blocks regression >5%

Eval is:
> not testing — it is a production gate

---

## 10. Failure Modes

| Failure | Behavior |
|--------|----------|
| OCR hallucination | drop or flag |
| missing data | explicit uncertainty |
| tool failure | partial response |
| verification failure | remove claim |

---

## 11. Brownfield Constraints

- OpenEMR is immutable source of truth
- No modification of core
- Module-only extension
- Follow CONTRIBUTING.md + README strictly

---

## 12. Forward Path

- critic agent
- additional document types
- improved retrieval

---

## Document Control
Version: 2.1