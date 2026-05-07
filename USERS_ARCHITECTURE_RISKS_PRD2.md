# ARCHITECTURE RISKS — PRD2 Modernization

## Document Version
- **Date**: 2026-05-06
- **Scope**: Clinical CoPilot PRD2 (Document Insight + Evidence Validation)
- **Risk Owner**: Architecture Team
- **Review Cadence**: Weekly during modernization sprint

---

## Executive Summary

PRD2 introduces two new capabilities beyond basic chart lookup: **document extraction** (UC4) and **guideline evidence validation** (UC5). These expand the attack surface and introduce novel failure modes not present in PRD1. This document catalogs architectural risks, their likelihood, impact, and mitigation status based on the 50-case golden dataset.

---

## High Severity Risks

### R01 — Citation Path Breakage (CRITICAL)
| Attribute | Value |
|-----------|-------|
| **Risk** | Citations reference non-existent JSON paths (e.g., `chart_lists.patient.not_real`) |
| **Evidence** | `chart_strip_*` cases (5 cases) expect `citation_present: false` |
| **Impact** | Model claims unsupported by data → Hallucination → Patient safety risk |
| **Likelihood** | High (20% of cases test this failure mode) |
| **Detection** | `citation_present` rubric in eval gate |
| **Mitigation** | Schema validation + path existence check before rendering citation |
| **Residual** | Medium — Requires runtime path resolution; can't prevent all invalid paths |

### R02 — PHI Leakage in Logs (CRITICAL)
| Attribute | Value |
|-----------|-------|
| **Risk** | Patient identifiers (e.g., `patient_id=12345`) appear in logs, metrics, or traces |
| **Evidence** | `phi_fail_*` cases (5 cases) expect `no_phi_in_logs: false` |
| **Impact** | HIPAA violation, compliance failure, potential breach |
| **Likelihood** | Medium (10% of cases intentionally fail) |
| **Detection** | `no_phi_in_logs` rubric + log scanner pattern matching |
| **Mitigation** | Log sanitization layer; structured logging with PHI field redaction |
| **Residual** | Low — Mitigation in place for known patterns; risk from novel PHI formats |

### R03 — Document Extraction Hallucination (HIGH)
| Attribute | Value |
|-----------|-------|
| **Risk** | Model invents lab values or document content not present in extracted data |
| **Evidence** | `doc_lab_*` cases (10 cases) test extraction accuracy |
| **Impact** | Incorrect clinical decision-making based on fabricated data |
| **Likelihood** | Medium (Lab extraction is complex; OCR errors compound) |
| **Detection** | `factually_consistent` rubric + cross-reference with source citation |
| **Mitigation** | Citation enforcement; require exact source path for every extracted claim |
| **Residual** | Medium — Citations verify source but not extraction accuracy itself |

---

## Medium Severity Risks

### R04 — Schema Validation Bypass (MEDIUM)
| Attribute | Value |
|-----------|-------|
| **Risk** | Model returns malformed JSON that does not conform to expected output schema |
| **Evidence** | `schema_bad_*` cases (5 cases) expect `schema_valid: false` |
| **Impact** | Downstream consumers (UI, EMR integration) fail or behave unexpectedly |
| **Likelihood** | Low (Baseline shows 50/50 pass on valid cases) |
| **Detection** | `schema_valid` rubric + JSON Schema validation at API boundary |
| **Mitigation** | Strict output schema with recursive validation; retry with retry on validation failure |
| **Residual** | Low — Schema validation is deterministic; only risk is new output fields |

### R05 — Guideline Mismatch (MEDIUM)
| Attribute | Value |
|-----------|-------|
| **Risk** | Evidence retrieval returns irrelevant or incorrect guideline chunks |
| **Evidence** | `guide_*` cases (5 cases) test guideline citation |
| **Impact** | AI cites wrong guideline → incorrect clinical recommendation |
| **Likelihood** | Medium (RAG retrieval quality varies by query phrasing) |
| **Detection** | `citation_present` + manual review of `guideline_evidence.chunks` relevance |
| **Mitigation** | Hybrid search (vector + keyword); chunk overlap; citation must quote source text |
| **Residual** | Medium — Depends on embedding quality and guideline corpus curation |

### R06 — False Safe Refusal (MEDIUM)
| Attribute | Value |
|-----------|-------|
| **Risk** | Model refuses to answer even when sufficient data exists |
| **Evidence** | `refusal_*` cases (10 cases) expect correct refusal shape; no false positive test |
| **Impact** | User frustration; reduced utility; clinician bypasses AI |
| **Likelihood** | Low (Current baseline shows perfect safe_refusal agreement) |
| **Detection** | `safe_refusal` rubric + monitoring refusal rate per query type |
| **Mitigation** | Confidence threshold tuning; allow override with explicit user confirmation |
| **Residual** | Low — Refusal decisions are conservative by design; acceptable trade-off |

---

## Low Severity Risks

### R07 — Performance Regression (LOW)
| Attribute | Value |
|-----------|-------|
| **Risk** | Latency increases due to additional extraction/retrieval steps |
| **Evidence** | Log samples: `retrieval_ms=3`, `tool:attach_and_extract` |
| **Impact** | Degraded user experience; clinician abandons tool |
| **Likelihood** | Medium during feature addition; Low after optimization |
| **Detection** | Latency metrics per operation type (P95, P99) |
| **Mitigation** | Parallel execution of extraction and retrieval; streaming responses |
| **Residual** | Low — Sub-second targets achievable with current architecture |

### R08 — Token Cost Escalation (LOW)
| Attribute | Value |
|-----------|-------|
| **Risk** | Document extraction + guideline retrieval increases prompt token count |
| **Evidence** | `phi_pass_*` logs show `prompt_tokens=400`, `estimated_usd=0.002` |
| **Impact** | Operational cost grows linearly with adoption |
| **Likelihood** | High (More features = more tokens) |
| **Detection** | Per-request token billing; cost alerts per tenant |
| **Mitigation** | Prompt compression; selective extraction; cache guideline chunks |
| **Residual** | Low — Cost is manageable at current volumes; needs review at scale |

---

## Risk Matrix

| Severity | Likelihood | Risks |
|----------|------------|-------|
| **CRITICAL** | High | R01 (Citation Breakage) |
| **CRITICAL** | Medium | R02 (PHI Leakage) |
| **HIGH** | Medium | R03 (Extraction Hallucination) |
| **MEDIUM** | Low | R04 (Schema Bypass) |
| **MEDIUM** | Medium | R05 (Guideline Mismatch) |
| **MEDIUM** | Low | R06 (False Refusal) |
| **LOW** | Medium | R07 (Performance) |
| **LOW** | High | R08 (Cost) |

---

## Mitigation Summary by Rubric

| Rubric | Risk Addressed | Gate | Status |
|--------|---------------|------|--------|
| `schema_valid` | R04 | CI blocking | ✅ Implemented |
| `citation_present` | R01, R03 | CI blocking | ✅ Implemented |
| `factually_consistent` | R03, R05 | CI blocking | ✅ Implemented |
| `safe_refusal` | R06 | CI blocking | ✅ Implemented |
| `no_phi_in_logs` | R02 | CI blocking + log scanner | ✅ Implemented |

---

## Required Actions

| Priority | Action | Owner | Target |
|----------|--------|-------|--------|
| P0 | Implement log sanitization for all PHI patterns | Security | This sprint |
| P0 | Add path existence validation before citation rendering | Backend | This sprint |
| P1 | Increase guideline test coverage from 5 to 20 cases | QA | Next sprint |
| P1 | Add hallucination detection for lab extraction | ML | Next sprint |
| P2 | Implement cost monitoring dashboard | DevOps | Month 2 |
| P2 | Performance baseline for extraction + retrieval | Architect | Month 2 |

---

## Review Sign-off

| Role | Signature | Date |
|------|-----------|------|
| Architecture Lead | | |
| Security Lead | | |
| Product Manager | | |
| Clinical Safety Officer | | |