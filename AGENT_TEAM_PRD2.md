Agent Role,Recommended Model,Why?
Lead Agent (The CEO),GPT-4o,"Best at high-level planning, task decomposition, and following complex system prompts."

Supervisor (The Manager),GPT-4o-mini,High instruction-following capability with significantly lower latency and cost for routing tasks.

Subagent 1: File Processor,Gemini 1.5 Flash,"Massive context window (up to 1M+ tokens) is perfect for ""reading"" entire patient PDFs or messy OCR logs."

Subagent 2: Summarizer,Llama 3.1 (70B),Excellent at structured JSON output; keeps clinical summaries concise and standard-compliant.

Subagent 3: EHR Writer,GPT-4o-mini,Great at calling OpenEMR APIs/Hooks to actually write data back into the PHP monolith.

# AGENT_TEAM_PRD2_MODERNIZED.md

# AI-Native Engineering Agent Team

## Lead Architect Agent

Model:
GPT-4o / Claude Sonnet

Responsibilities:
- architecture planning
- system decomposition
- tradeoff analysis
- modernization strategy

---

## Supervisor Agent

Model:
GPT-4o-mini

Responsibilities:
- workflow routing
- task coordination
- validation enforcement
- documentation synchronization

---

## Frontend Engineering Agent

Responsibilities:
- React/Next.js generation
- component scaffolding
- API integration patterns

---

## OpenEMR Compatibility Agent

Responsibilities:
- verify API compatibility
- prevent backend assumptions
- preserve upstream integrity

---

## Security Audit Agent

Responsibilities:
- auth review
- frontend attack analysis
- dependency review
- HIPAA considerations

---

## Performance Agent

Responsibilities:
- frontend optimization
- rendering efficiency
- bundle analysis
- caching strategy

---

## Documentation Agent

Responsibilities:
- repo documentation
- onboarding materials
- architecture synchronization

---

## Verification Agent

Responsibilities:
- regression testing
- schema validation
- workflow verification

---

# Agent Governance Rules

- no autonomous deployment
- no unsupervised merge approval
- human accountability mandatory
- all outputs auditable
- rollback capability preserved

---

# Final Position

AI agents accelerate:
- planning
- analysis
- implementation
- verification

Humans remain responsible for:
- production decisions
- healthcare safety
- security
- governance