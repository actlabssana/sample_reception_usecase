# SR2-003 — On-Prem / Self-Hosted LLM Feasibility & TCO

**Epic:** AI Sample Reception (Build)  ·  **Story:** self-hosted LLM feasibility + TCO vs cloud API
**Date:** 2026-07-01  ·  **Author:** Data Science

> **One-line finding.** For Actlabs sample-reception extraction, the LLM token cost is
> negligible at any realistic lab volume (**~$700–$2,100/yr** on Claude with batch +
> caching at 500 forms/day). Self-hosting a 70B/Cohere model costs **$44k–$200k/yr** and
> is roughly **accuracy-competitive** but **not cost-competitive** — its only rational
> justification is **data residency / compliance**, not price. Recommendation: **start on
> the cloud API; self-host only if a lab-data-residency mandate requires it.**

---

## 1. Scope & pipeline

The task (from the SR2-002 baseline work) is: parse a sample-reception document → OCR/plain
text → extract 41 canonical fields (`Actlabs_Unified_Data_Dictionary.xlsx`) as structured
JSON. Two stages, each of which can be cloud or self-hosted **independently**:

```
   Document ──▶  [ OCR / layout ]  ──▶  text  ──▶  [ LLM extraction ]  ──▶  JSON (41 fields)
                 AWS / GCP / Azure            open models / Cohere / Claude API
                 or self-hosted OCR
```

**Data-residency corollary (important):** if the driver for self-hosting is keeping lab data
on-prem, the **OCR stage must also be self-hosted**. Sending pages to AWS Textract / GCP
Document AI / Azure Doc Intelligence egresses the raw document and defeats the purpose. The
self-hosted-OCR options are Tesseract / PaddleOCR / docTR / Surya, or a vision-language model
(Qwen2-VL, Llama-3.2-Vision) that does OCR+extraction in one pass. See §6.

---

## 2. GPU sizing & sustained throughput  *(AC #1)*

Full table in [`tco_results.md`](tco_results.md) §1. Capacity = sustained batched **output**-token
throughput (vLLM estimate) over an 8h same-day SLA window at 60% utilisation, ÷ 1,500 output
tokens/doc.

| Model / GPU set | GPUs | Out tok/s (est.) | **Docs/day/set** |
|---|--:|--:|--:|
| Llama-3.1-8B (1× L40S 48GB) | 1 | 3,000 | **34,560** |
| Mistral-Small-24B (1× H100) | 1 | 2,200 | 25,344 |
| Llama-3.3-70B (1× H100, AWQ 4-bit) | 1 | 1,500 | 17,280 |
| Llama-3.3-70B (2× A100 80GB) | 2 | 1,200 | 13,824 |
| Cohere Command-A/R+ (4× A100 80GB) | 4 | 900 | 10,368 |

**Sizing takeaway:** even the largest model on the smallest viable GPU set clears **>10,000
forms/day** — an order of magnitude above the "Scale" scenario (2,000/day). **One GPU node is
enough** for the foreseeable Actlabs volume; the workload would leave that node **heavily
under-utilised** unless it is shared with other lab AI workloads. This is itself an argument
against dedicating capex to this task alone. (Throughput figures are order-of-magnitude vLLM
estimates — confirm with a load test before committing capex.)

---

## 3. Three-year TCO  *(AC #2)*

Annualised (capex amortised over 3 yr), USD. Full breakdown per scenario in
[`tco_results.md`](tco_results.md) §2; model + assumptions in [`tco_model.py`](tco_model.py).

### Headline — **Production, 500 forms/day (130,000/yr)**

| Option | Annual | vs cheapest cloud |
|---|--:|--:|
| **Claude Haiku 4.5** (Batch + cache) | **$702** | 1× |
| **Claude Sonnet 5** (Batch + cache) | **$2,106** | 3× |
| Claude Opus 4.8 (Batch + cache) | $3,510 | 5× |
| Self-host Llama-3.1-8B (on-prem, 1× L40S) | $44,460 | 63× |
| Self-host Llama-3.3-70B (on-prem, 1× H100) | $51,586 | 73× |
| Cloud-rented H100 (Llama-70B, reserved 24×7) | ~$64,000 | 91× |
| Cohere Command-A/R+ private (on-prem, 4× A100) | $202,769 | 289× |

Self-hosted cost is **dominated by fixed cost** (GPU capex/rental + the MLOps FTE share +,
for Cohere, licensing) — it barely moves with volume. Cloud API cost is **purely variable**
($0.0054–$0.027/doc) and scales linearly:

| Volume | Haiku (Batch) | Sonnet (Batch) | Opus (Batch) | On-prem 8B | Cohere on-prem |
|---|--:|--:|--:|--:|--:|
| Pilot 100/day (26k/yr) | $140 | $421 | $702 | $44,460 | $202,769 |
| Production 500/day (130k/yr) | $702 | $2,106 | $3,510 | $44,460 | $202,769 |
| Scale 2000/day (520k/yr) | $2,808 | $8,424 | $14,040 | $44,460 | $202,769 |

**Break-even:** on-prem 8B ($44k/yr fixed) does not beat Claude Haiku-Batch until roughly
**8 million forms/year** (~30,000/day) — ~15× the Scale scenario. At Actlabs volumes there is
**no cost crossover** where self-hosting wins. (Prompt caching cuts the reused schema/prompt
prefix to 0.1×; the Batch API halves token price for the same-day, non-interactive workload —
both apply cleanly here.)

---

## 4. Accuracy — self-hosted vs cloud  *(AC #3)*

A reproducible spot-check harness is committed under [`accuracy_spotcheck/`](accuracy_spotcheck/):
it runs any OpenAI-compatible model (vLLM/Ollama/Cohere-private) over the 19-doc gold set using
the **same OCR text** the regex baseline saw, and scores it with the **same 14-field / 167-instance**
metric as the SR2-002 scorecard.

| Pipeline | Strict | Weighted | Status |
|---|--:|--:|---|
| Regex baseline (SR2-002, current code) | 23.9% | 31.7% | **measured** |
| Self-host 8B (Llama-3.1 / Mistral) | ~60–72% | ~70–80% | estimate — run harness |
| Self-host 70B (Llama-3.3 / Qwen2.5-72B) | ~82–90% | ~88–94% | estimate — run harness |
| Cohere Command-A/R+ (private) | ~82–90% | ~88–94% | estimate — run harness |
| **Cloud ref — Claude Sonnet/Opus** | **~90–96%** | **~93–97%** | estimate — run harness |

> **These are class-level estimates, not Actlabs-measured numbers.** GPUs are not available in
> this environment, so the harness is provided to produce the real figures on lab hardware
> (`accuracy_spotcheck/README.md`). They are anchored on the baseline's failure modes being
> almost entirely **layout / field-bleed** (which any LLM reading the same text largely fixes)
> plus each model tier's documented document-extraction behaviour.

**Accuracy conclusion:** a self-hosted 70B model is expected to land within **single-digit
percentage points** of the cloud API on the header fields. **Accuracy does not decide
self-hosted-vs-cloud** — cost (§3), data residency (§5), and ops burden (§7) do. Structured
Rev2.0 fields (reporting grid, disposition matrix, sign-off) will separate the tiers more —
measure them explicitly by extending the scorer's field list.

---

## 5. Data-residency & compliance benefits  *(AC #4)*

The one place self-hosting wins decisively. Relevant because sample-reception forms carry
**client PII** (names, emails, phones, addresses), **commercial terms** (quotes/PO/pricing),
and **PCI credit-card data** (already dropped by policy — never persist).

| Benefit | Self-hosted (on-prem / VPC) | Cloud LLM API |
|---|---|---|
| Data leaves premises | **No** (stays in Actlabs network) | Yes — sent to provider |
| Data residency (Canada / Ontario) | **Fully controlled** | Depends on provider region / DPA |
| Training on your data | **Impossible** (your weights, your infra) | Contractually excluded, but requires trust in DPA |
| PII / PCI blast radius | Contained to owned infra | Extends to provider + subprocessors |
| Air-gap capable | **Yes** | No |
| Audit / certification scope | You control the boundary | Rely on provider SOC2/ISO + DPA |
| Client contractual "no-cloud" clauses | **Satisfiable** | Not satisfiable |

**Cloud-side mitigations that narrow the gap (cheaper than self-hosting):** provider Zero-Data-
Retention (ZDR) so prompts aren't stored; a region-pinned / data-residency deployment; a signed
DPA with no-training terms; PII redaction/tokenisation before send; and never transmitting the
PCI block. For most labs these controls are sufficient. **Self-hosting is warranted specifically
when a client contract or regulator mandates that lab data must not leave Actlabs-controlled
infrastructure at all** — then the $44k–$200k/yr is the price of compliance, not of accuracy.

---

## 6. OCR options considered (AWS / GCP / Azure + self-hosted)

The extraction quality ceiling is set by OCR/layout quality on messy multi-column forms — the
exact place the regex baseline failed. Options:

| OCR option | Type | ~Cost | Data residency | Notes |
|---|---|---|---|---|
| AWS Textract (Forms+Tables/Queries) | Cloud | ~$0.065/pg | Egresses data | Best-in-class forms/tables + Queries API |
| GCP Document AI (Form Parser / custom) | Cloud | ~$0.030/pg | Egresses data | Custom processors trainable on Actlabs forms |
| Azure AI Document Intelligence (Layout/custom) | Cloud | ~$0.010/pg | Egresses data | Cheapest cloud; strong custom-model story |
| Tesseract | Self-hosted | $0 (compute) | **On-prem** | Weak on complex layout; OK baseline |
| PaddleOCR / docTR / Surya | Self-hosted | $0 (compute) | **On-prem** | Modern; good layout + tables; GPU-accelerated |
| VLM (Qwen2-VL, Llama-3.2-Vision) | Self-hosted | GPU | **On-prem** | OCR **and** extraction in one model — simplest residency story |

**Cloud OCR add-on cost** (if outsourced) at 500/day is ~$3,900–$25,000/yr (see `tco_results.md`)
— small next to the fixed GPU cost, but note it **re-introduces data egress**. For a residency-
driven deployment, pair self-hosted OCR (PaddleOCR/docTR) **or** a self-hosted VLM with the
self-hosted LLM so no page ever leaves the network. For a cloud deployment, Azure DI or GCP
Document AI custom processors are the strongest accuracy play and pair naturally with the cloud LLM.

---

## 7. Assumptions & risks  *(Definition of Done)*

**Assumptions** (all overridable in `tco_model.py`):
- **Volume is unknown** — SR2-003 supplied no intake figure. Modelled at 100 / 500 / 2,000
  forms/day. *This is the single biggest input; replace with real numbers and re-run.*
- Token profile: 3,000 var-in + 3,000 cacheable-in + 1,500 out per doc; 3 pages/doc.
- Infra: capex amortised 3 yr; power $0.10/kWh @ PUE 1.5; MLOps FTE $160k loaded, 25% allocated
  (open models) / 15% (Cohere, vendor-supported).
- Claude pricing per claude-api skill (2026-06-24): Haiku $1/$5, Sonnet $3/$15 (intro $2/$10 to
  2026-08-31), Opus $5/$25 per 1M tok; cache read 0.1×; Batch 0.5×.
- **Cohere private-deployment license = $150k/yr is a PLACEHOLDER** (negotiated, not public) —
  get a real quote before deciding.
- GPU throughput = vLLM batched-serving estimates (order-of-magnitude).
- Environment limits: PHP/Composer and GPUs are unavailable here, so self-hosted accuracy is
  **estimated**, not measured — the committed harness produces the real numbers on lab hardware.

**Risks:**
- *Throughput optimism* — real vLLM tps depends on batch size, seq length, quantisation; a load
  test could move docs/day ±40%. Low impact (still massively over-provisioned for the volume).
- *Under-utilisation* — one GPU node dwarfs the workload; capex is wasted unless shared with
  other lab AI. Favours cloud unless a GPU already exists on-prem.
- *Ops burden underestimated* — self-hosting adds model updates, GPU driver/CUDA maintenance,
  monitoring, on-call, security patching. The 0.25-FTE estimate is optimistic for a team new to
  GPU serving; a first deployment can consume far more.
- *Accuracy estimates unverified* — must run `accuracy_spotcheck/` on hardware before any
  self-host commitment. If 8B underperforms the estimate, the GPU tier (and cost) rises.
- *Cohere licensing unknown* — the placeholder could be materially higher; it dominates that
  option's TCO.
- *Cloud-price / intro-pricing drift* — Sonnet intro pricing ends 2026-08-31; re-baseline before
  budgeting.
- *Residency scope creep* — if cloud OCR is used "just for OCR", data still egresses; a partial
  self-host doesn't satisfy a strict no-cloud mandate.

---

## 8. Recommendation

1. **Default to the cloud API** (Claude Haiku or Sonnet) with **Batch + prompt caching**. At
   Production volume it is **~$700–$2,100/yr**, is the fastest to stand up, needs no GPU ops,
   and is expected to be the most accurate.
2. **Self-host only under a data-residency mandate.** If a client/regulator requires lab data to
   stay on Actlabs infrastructure, deploy a **70B open model (Llama-3.3 / Qwen2.5-72B) on a single
   H100** *plus self-hosted OCR (PaddleOCR/docTR) or a VLM* — ~**$50k/yr**, near-cloud accuracy,
   full residency. Prefer **Cohere private** only if you need vendor support/SLA and the negotiated
   license is justified.
3. **Before committing to either:** (a) get the real intake volume and re-run `tco_model.py`;
   (b) run `accuracy_spotcheck/` on candidate models to replace the estimated accuracy with
   measured numbers; (c) get a real Cohere quote if that path is live.

## Deliverables in this folder
- `tco_model.py` — parameterised GPU-sizing + TCO model (**committed**, AC #1/#2)
- `tco_results.md` / `tco_results.json` — generated sizing + TCO tables
- `accuracy_spotcheck/` — reproducible self-hosted-vs-cloud accuracy harness + methodology (AC #3)
- this report — residency/compliance analysis, assumptions & risks (AC #4, DoD)
