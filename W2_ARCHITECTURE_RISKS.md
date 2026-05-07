# PRD 2 Architecture Risks

## 1. Multi-Agent Complexity

Risk:
- Supervisor becomes opaque

Mitigation:
- log all routing decisions
- limit agent scope
- enforce deterministic flow

---

## 2. Extraction Risk (Critical)

Risk:
- system generates false structured data

Mitigation:
- treat extraction as untrusted input
- require schema validation
- require citation linkage

---

## 3. Write Boundary Risk

Risk:
- ingestion tool writes incorrect data

Mitigation:
- server-controlled persistence only
- model cannot write directly
- validation before storage

---

## 4. Observability Drift

Risk:
- losing PRD 1 traceability

Mitigation:
- AgentTelemetry remains source of truth
- **LangChain** for orchestration when introduced (**planned — not yet in tree**); until then, keep the OpenAI tool-loop path inspectable

---

## 5. Eval Failure Risk

Risk:
- regressions pass unnoticed

Mitigation:
- CI blocking gate
- 50-case dataset
- strict boolean scoring