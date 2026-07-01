# SR2-004 — Build-vs-Buy Decision Matrix & Recommendation

**Sample-Reception Field Extraction — Cloud API vs On-Prem vs Python-Only**

**Epic:** AI Sample Reception (Build)  ·  **Story:** SR2-004  ·  **Date:** 2026-07-01  ·  **Author:** AI PM / Data Science

---

> **Recommendation.** Adopt the **Cloud LLM API (Claude Haiku/Sonnet, Batch + prompt caching)** as the default extraction engine — it scores highest across the weighted matrix (86/100), costs ~$700–$2,100/yr at production volume, is fastest to ship, and is expected to be the most accurate. **Self-host a 70B open model + self-hosted OCR only if a client/regulator mandates that lab data never leave Actlabs infrastructure** (~$50k/yr — the price of compliance, not of accuracy). **Retire the Python-only regex baseline** as a primary path: at 23.9% strict accuracy it is not fit for production, though it stays useful as a cheap pre-filter and offline fallback.

---

## 1. Purpose & scope

This document converts the SR2-003 feasibility and TCO analysis into a leadership-ready decision. It scores three delivery options against six weighted criteria drawn from the acceptance criteria, then makes a build-vs-buy and cloud-vs-on-prem recommendation with an engineering path forward. The task is unchanged from SR2-002/003: parse a sample-reception document, OCR to text, and extract 41 canonical fields as structured JSON.

Three options are on the table:

- **Cloud API —** Claude (Haiku/Sonnet/Opus) via the Batch API with prompt caching. Buy.
- **On-prem self-host —** a 70B open model (Llama-3.3 / Qwen2.5-72B) on a single H100, paired with self-hosted OCR (PaddleOCR/docTR) or a VLM; Cohere private is the vendor-supported variant. Build.
- **Python-only —** the existing SR2-002 regex/heuristic baseline, no LLM. Build (already built).

## 2. Scoring method

Each option is scored 1–5 (5 = best) on six criteria. Criteria are weighted to reflect what actually drives this decision for a lab handling client PII: accuracy and compliance dominate, cost matters but is small in absolute terms, and latency is non-critical for a same-day batch SLA. Weighted total = Σ(weight × score ÷ 5), out of 100. Scores are grounded in SR2-003; accuracy figures are class-level estimates except the measured regex baseline.

| Criterion | Weight | Why it is weighted this way |
|---|:--:|---|
| Accuracy | 25 | Extraction quality is the core value; errors mean re-keying or bad data downstream. |
| $/doc & TCO | 20 | Real money, but negligible in absolute terms at lab volume — hence not the top weight. |
| Compliance / residency | 20 | Forms carry PII, commercial terms, and (dropped) PCI data. Can be a hard contractual gate. |
| Maintenance / ops | 15 | Ongoing burden differs sharply; GPU serving is a new competency for the team. |
| Latency | 10 | Same-day batch SLA — sub-second response is not required, so low weight. |
| Build effort | 10 | Time-to-value matters but is one-time; low weight relative to run-cost concerns. |

## 3. Scored decision matrix

Scores shown per option; the weighted total appears in the final row.

| Criterion (weight) | Wt | Cloud API | On-prem | Python-only | Basis (from SR2-003) |
|---|:--:|:--:|:--:|:--:|---|
| Accuracy | 25 | 5 | 4 | 1 | Cloud ~90–96% strict; 70B ~82–90%; regex 23.9% measured |
| $/doc & TCO | 20 | 5 | 2 | 5 | Cloud $0.005–0.027/doc; on-prem $44k–203k/yr fixed; regex ~$0 |
| Compliance / residency | 20 | 2 | 5 | 5 | Cloud egresses (mitigable); on-prem/regex stay on network |
| Maintenance / ops | 15 | 5 | 2 | 3 | No GPU ops vs GPU/CUDA/on-call vs brittle regex upkeep |
| Latency | 10 | 4 | 4 | 5 | All meet same-day SLA; regex instant |
| Build effort | 10 | 5 | 2 | 2 | API fastest; GPU deploy heavy; regex coverage manual |
| **WEIGHTED TOTAL (/100)** | | **86.0** | **66.0** | **68.0** | Cloud API leads clearly |

*Read the near-tie for second place carefully:* on-prem and Python-only land close together for opposite reasons — on-prem is strong on accuracy and residency but expensive and ops-heavy; Python-only is cheap and on-prem but fails on accuracy. Neither is a general-purpose winner.

## 4. Cost context (production, 500 forms/day ≈ 130k/yr)

The matrix scores TCO relatively; the absolute figures show why cost cannot justify self-hosting at Actlabs volume. There is no cost crossover where self-hosting wins until ~8 million forms/year — roughly 15× the modelled Scale scenario.

| Option | Annual | vs cheapest | Cost shape |
|---|--:|:--:|:--:|
| **Claude Haiku 4.5 (Batch + cache)** | **$702** | 1× | Variable |
| Claude Sonnet 5 (Batch + cache) | $2,106 | 3× | Variable |
| Claude Opus 4.8 (Batch + cache) | $3,510 | 5× | Variable |
| Self-host Llama-3.1-8B (1× L40S) | $44,460 | 63× | Fixed |
| Self-host Llama-3.3-70B (1× H100) | $51,586 | 73× | Fixed |
| Cloud-rented H100 (Llama-70B, 24×7) | ~$64,000 | 91× | Fixed |
| Cohere Command-A/R+ private (4× A100) | $202,769 | 289× | Fixed + license |

**Note:** the Cohere $150k/yr license is a placeholder pending a real quote, and self-hosted accuracy figures are estimates until the `accuracy_spotcheck/` harness is run on lab GPUs. Both are flagged as pre-commitment gates below.

## 5. Recommendation & rationale

**5.1  Default: Cloud API (Claude Haiku or Sonnet, Batch + caching).**

It wins the weighted matrix (86/100), is the fastest to stand up, carries no GPU-ops burden, and is expected to be the most accurate. At production volume the token spend is ~$700–$2,100/yr — small enough that cost is not a deciding factor. Start on Haiku; escalate to Sonnet if measured accuracy on the structured Rev2.0 fields requires it.

**5.2  Conditional: On-prem 70B + self-hosted OCR — only under a data-residency mandate.**

Self-hosting wins decisively on exactly one axis: keeping lab data on Actlabs-controlled infrastructure. If a client contract or regulator requires that data must not leave the network at all, deploy a 70B open model on a single H100 plus self-hosted OCR (PaddleOCR/docTR) or a VLM so no page ever egresses — ~$50k/yr for near-cloud accuracy. Choose Cohere private only if vendor support/SLA is required and the negotiated license justifies it. Absent such a mandate, cloud-side controls (ZDR, region-pinning, a no-training DPA, PII redaction, never sending the PCI block) narrow the compliance gap far more cheaply.

**5.3  Not recommended as a primary path: Python-only regex baseline.**

At 23.9% strict / 31.7% weighted accuracy (measured), the regex baseline is not production-viable on its own — its failure modes are exactly the layout and field-bleed errors any LLM reading the same OCR text largely fixes. It remains valuable as a near-zero-cost pre-filter, a sanity check, and an offline fallback, but it does not meet the extraction bar leadership is buying.

## 6. Engineering path forward

**Immediate (this sprint):**

- **Stand up the Cloud API path:** Claude via Batch API + prompt caching against the 41-field schema; wire in the existing extraction prompt.
- **Apply cloud-side controls by default:** ZDR, region-pinning, signed no-training DPA, PII redaction pre-send, and hard-drop the PCI block.
- **Run `accuracy_spotcheck/` against Haiku and Sonnet** on the 19-doc gold set to replace estimated accuracy with measured numbers.

**Before any self-host commitment (gates):**

- Obtain the real intake volume and re-run `tco_model.py` — volume is the single biggest unknown input.
- Run the accuracy harness on candidate self-hosted models (8B and 70B) on lab GPU hardware; if 8B underperforms, the GPU tier and cost rise.
- Get a real Cohere private-deployment quote if that path is live — the $150k/yr license is a placeholder that dominates its TCO.
- Confirm whether any client contract or regulator actually mandates no-cloud residency; this single fact decides cloud-vs-on-prem.

**Watch items:**

- Sonnet intro pricing ends 2026-08-31 — re-baseline the budget before then.
- Extend the scorer's field list to the structured Rev2.0 fields (reporting grid, disposition matrix, sign-off); these will separate model tiers more than the header fields do.

## 7. Decision summary

| Question | Decision | Condition / rationale |
|---|---|---|
| Build vs Buy? | **Buy (Cloud API)** | Highest matrix score, lowest cost at volume, fastest to ship, most accurate. |
| Cloud vs On-prem? | **Cloud (default)** | On-prem only if a no-cloud data-residency mandate exists. |
| Keep Python-only? | **No (as primary)** | 23.9% accuracy; retain as pre-filter / offline fallback only. |
| Model to start on? | **Claude Haiku** | Escalate to Sonnet if measured Rev2.0-field accuracy requires it. |

---

*Source analysis: SR2-003 Feasibility & TCO (`tco_model.py`, `tco_results.md`, `accuracy_spotcheck/`). Accuracy figures are class-level estimates except the measured regex baseline; confirm on lab hardware before any capex commitment.*
