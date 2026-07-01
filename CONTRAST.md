# CONTRAST — Arm A (naive job chain) vs Arm B (durable workflow)

Side-by-side notes for each P0 behaviour. Arm B placeholders will be filled when
`TriageWorkflow` and harness demos are wired in Phase 2.

---

## P0-1 Fan-out enrichment

| | Arm A (naive) | Arm B (durable) |
|---|---|---|
| **Mechanism** | `ClassifyJob` → `CorrelateJob` → `RunbookJob` — each dispatches the next on success. Strictly sequential. | `yield all([ClassifyActivity, CorrelateActivity, RunbookActivity])` — parallel fan-out, fan-in before synthesis. |
| **Moving parts** | 3 queue jobs + incident row mutations between steps | 1 workflow yield + 3 activities |
| **Latency** | Wall-clock time sums all three LLM calls | Overlap — bounded by slowest activity |
| **LOC (orchestration only)** | ~120 lines across 3 jobs + chain dispatches | _TBD — workflow execute() stub_ |

**Foil point:** Arm A pays triple latency and never overlaps I/O; no coordinator beyond "dispatch next job".

---

## P0-2 Selective retry

| | Arm A (naive) | Arm B (durable) |
|---|---|---|
| **Mechanism** | Laravel queue retry re-runs the **entire failed job** `handle()`. No step-level idempotency keys, no "already done" guards. | Framework replays workflow code; failed activity retries **only that activity** — earlier yields are not re-executed. |
| **Duplicate side effects** | **Yes, by design.** Re-running `ClassifyJob` calls the LLM again. Re-dispatching `SynthesiseJob` duplicates synthesis even if classify/correlate/runbook already ran. | Assert via `FakeLlmClient` call-count: exactly one call each for classify/correlate/runbook after synthesise retry. |
| **Guards needed in Arm A** | Per-step `if ($incident->severity) return;` checks, or stored step tokens — hand-rolled and easy to get wrong | None in domain code — orchestrator owns replay semantics |
| **LOC (orchestration only)** | 0 lines of guards today (intentionally awkward) | _TBD_ |

**Foil point:** AC-5 — a forced synthesise failure without guards produces duplicated LLM calls. Tests in `NaiveTriagePipelineTest::test_retry_duplicates_llm_calls_without_idempotency_guard` document this.

---

## P0-3 Crash-resume

| | Arm A (naive) | Arm B (durable) |
|---|---|---|
| **Mechanism** | Pipeline state is whatever was last written to the `incidents` row before the worker died. No event log; no automatic resume of "in-flight" step. | Workflow event stream replays from last durable checkpoint; worker death is transparent. |
| **Mid-step crash** | Job may have called LLM but not updated DB → retry duplicates call. Job may have updated DB but not dispatched next → manual intervention or duplicate dispatch risk. | Activity either completed (recorded) or not — replay continues from yield boundary. |
| **Resume story** | Re-queue from harness / hope incident status is enough to infer position | `queue:work` + workflow replay |
| **LOC (orchestration only)** | Status column + implicit position in job chain | _TBD_ |

**Foil point:** Arm A has no first-class "where was I?" primitive beyond mutating columns and guessing which job to fire next.

---

## P0-4 Human gate with timeout

| | Arm A (naive) | Arm B (durable) |
|---|---|---|
| **Mechanism** | `SynthesiseJob` sets `status = awaiting_approval`, dispatches `ReCheckApprovalJob` (poll, re-schedules with delay) **and** `EscalateCheckJob` (one-shot timeout). Human sets `approved` / `rejected` on the row out-of-band. | `Workflow::awaitWithTimeout` racing an approve **signal** vs timer — _exact API TBD (O-1)_. |
| **Approval path** | Poll job reads status column → `ActJob` or `EscalateJob` | Signal received → `ActActivity` |
| **Timeout path** | Separate scheduled `EscalateCheckJob` — must stay in sync with poll job; no atomic race | Timer branch in workflow — single declarative construct |
| **Awkward bits** | Two independent schedulers; poll loop hammers queue; sync driver runs delayed jobs immediately (test hazard); no dedupe if both timeout and approve fire close together | _TBD_ |
| **LOC (orchestration only)** | `SynthesiseJob` + `ReCheckApprovalJob` + `EscalateCheckJob` (~80 lines) | _TBD_ |

**Foil point:** The human gate is a status column plus two polling jobs instead of one signal/timer abstraction.

---

## Summary table

| Behaviour | Arm A cost | Arm B cost (when wired) |
|---|---|---|
| Fan-out | Sequential jobs, 3× latency | `yield all([...])` |
| Selective retry | None — duplicates LLM calls | Framework-level per-activity retry |
| Crash-resume | Row snapshots only | Event-stream replay |
| Human gate | Status + 2 scheduled jobs | Signal + timer (_TBD_) |

---

## Files (Arm A)

- Entry: `app/Services/NaiveTriagePipeline.php`
- Jobs: `app/Jobs/Naive/*`
- Shared step logic: `app/Jobs/Naive/TriageStepRunner.php` (mirrors activity DTOs / `LlmClient` calls — activities invoke the same runner when Agent 1/2 wire them)
- Tests: `tests/Feature/Naive/NaiveTriagePipelineTest.php`
