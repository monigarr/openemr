---
name: agent-team-prd2
description: >-
  Use the PRD2 agent rosters: Clinical CoPilot (PHP module) vs PRD2 Modernized (Next.js).
  Apply when orchestrating multi-step work, choosing a model, or delegating with an explicit role prompt.
---

# AGENT_TEAM PRD2 — delegation (Cursor)

## Canonical rosters (path-scoped)

1. **Clinical CoPilot** (`interface/modules/custom_modules/oe-module-clinical-copilot/**`): [`.cursor/rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc`](../../rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc) — summary; full model/flow detail in [`.cursor/rules/PRD-2-AgentForge-Agent-Roster.mdc`](../../rules/PRD-2-AgentForge-Agent-Roster.mdc).
2. **PRD2 Modernized** (`frontend/**`, modernization docs under `Documentation/**`): [`.cursor/rules/AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc`](../../rules/AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc). Requirements: [`Documentation/PRD2_MODERNIZED.md`](../../../Documentation/PRD2_MODERNIZED.md).

Follow the **roster whose globs match the files you are editing**; for cross-cutting changes, follow **both** rosters and their path rules.

## How “subagents” work here

Cursor loads rules by scope (`globs` / `alwaysApply`). This repo does **not** define separate executable subagent processes; **you** (or the lead agent) simulate subagents by:

1. Stating the **role** at the start of a subtask (e.g. “Acting as Security Audit Agent: …”).
2. Selecting the **model** in the UI to match the roster when possible (e.g. heavier model for Security / Architect).
3. Keeping **handoffs inspectable**: inputs, outputs, and which track (Clinical CoPilot vs Modernized) applied.

## When to use which track

| If the task touches… | Use |
| -------------------- | --- |
| `oe-module-clinical-copilot/**` | [AGENT-TEAM-PRD2-Subagents-Roster](../../rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc) + [PRD-2-AgentForge-Agent-Roster](../../rules/PRD-2-AgentForge-Agent-Roster.mdc) |
| `frontend/**`, dashboard FHIR/OAuth | [AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster](../../rules/AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc) + [PRD2-Modernized-Scope](../../rules/PRD2-Modernized-Scope-and-Docs.mdc) + [PRD2-Modernized-Frontend-Security](../../rules/PRD2-Modernized-Frontend-Security.mdc) |
| `Documentation/**` PRD2 modernized markdown | [AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster](../../rules/AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc) + Architect-style review as needed |

## Suggested Composer / agent instructions (copy pattern)

```text
Role: <e.g. fhir-client-builder or Supervisor>
Model intent: <e.g. GPT-4o-mini>
Scope: <paths or APIs>
Rules: follow the path-matching roster (.cursor/rules/AGENT-TEAM-PRD2-Subagents-Roster.mdc OR AGENT-TEAM-PRD2-MODERNIZED-Subagents-Roster.mdc) and product-specific rules for this path.
Deliverable: <artifact>
Stop condition: <what “done” means; human review if merge/deploy>
```

## Governance

Do not override: no autonomous deploy, no unsupervised merge approval, human accountability, auditable outputs, rollback preserved — see the non-negotiables in the roster rules above.
