# SPEC — Durable Triage POC

> A spec-driven-development artefact. This file is the contract that the parallel
> coding agents build against. Treat the **Shared Contracts** section as frozen
> before any parallel work starts — that is the gate.

---

## 1. Problem Statement

A multi-step LLM pipeline (classify → correlate → synthesise → human-approve → act)
is the kind of process where naive queue-chaining quietly hurts: a crash mid-flight
loses in-progress state, a failure in the final step re-runs expensive earlier LLM
calls, parallel enrichment has to be hand-coordinated, and "wait for a human, but
escalate after N minutes" has no clean primitive. The POC builds the **same pipeline
two ways** — a naive queued-job chain and a durable workflow — so the decision
boundary between "a queue job is enough" and "you need durable orchestration" stops
being abstract and becomes something you can see fail and see fixed.

This is a **learning POC**, not a product. Its output is comprehension plus a runnable
contrast demo, plus a written evaluation of the parallel-agent dev tooling used to
build it.

**Domain:** incident triage (reuses an existing experiment). Swappable for the
career-ops scoring pipeline without changing the architecture — the domain is a thin
shell over four LLM activities and one human gate.

---

## 2. Goals

1. **Make the boundary legible.** Demonstrate, side by side, four behaviours where
   the durable arm wins and the naive arm needs bodging: crash-resume, selective
   retry, fan-out/fan-in, and a human-approval gate with a durable timeout.
2. **Build durable-execution muscle.** End the POC able to reason about determinism,
   idempotency, replay, signals, and timers without re-reading the docs each time.
3. **Evaluate the parallel-agent dev tool in anger.** Produce honest notes on its
   ergonomics and overhead on a small, tightly-coupled repo — the real second
   deliverable.
4. **Exercise spec-driven flow.** Drive the build from this spec; measure how well a
   frozen-contracts spec actually enables clean parallel fan-out.

## 3. Non-Goals

- **Production readiness.** No real incident source, no real paging integration, no
  auth hardening. Fakes and fixtures throughout.
- **Rebuilding the canonical saga demo.** The upstream travel-agent saga already
  showcases compensation; this POC deliberately picks different feature surfaces
  (resume, selective retry, fan-out, signal+timer).
- **The 2.0 standalone-server architecture.** Targeting stable v1 (single-process,
  queue-backed). v2 is a parked follow-up, not this build.
- **Model quality.** Whether the triage suggestions are *good* is irrelevant; the
  orchestration is the subject. A cheap model or even a canned-response stub is fine.
- **A polished UI.** Waterline is the UI. A thin artisan-command harness is the only
  bespoke interface.

---

## 4. The Two Arms

### Arm A — Naive queued-job chain (the foil)
Each step is a `ShouldQueue` job that dispatches the next on success. Pipeline state
lives in an `incidents` row that each job mutates. The approval wait is a `status`
column plus a scheduled re-check command; the timeout escalation is a separate
scheduled command. **This arm is meant to be visibly awkward** — its job is to make
the contrast land, not to be defended.

### Arm B — Durable workflow (the subject)
One `TriageWorkflow extends Workflow`. Steps are activities yielded from `execute()`.
Parallel enrichment via `yield all([...])`. The human gate is a workflow **signal**;
the escalation is a workflow **timer** racing the signal. State is the event stream;
resume is free.

Both arms run the *same* activity logic where possible — the activities are shared, so
the only real difference under test is the orchestration layer.

---

## 5. Shared Contracts  *(FREEZE BEFORE PARALLEL WORK)*

These are the seams. They must exist and be committed before any agent fans out,
or the agents collide. Define them in the serial spine phase.

**Domain model**
- `Incident` — id, raw_payload, severity (nullable), correlation_ids (json),
  runbook_refs (json), suggestion (nullable text), status, timestamps.

**Activity I/O DTOs** (plain readonly classes or arrays — pick one and hold it)
- `ClassifyInput { incidentId }` → `ClassifyResult { severity, confidence }`
- `CorrelateInput { incidentId }` → `CorrelateResult { correlationIds[] }`
- `RunbookInput { incidentId }` → `RunbookResult { runbookRefs[] }`
- `SynthesiseInput { incidentId, severity, correlationIds[], runbookRefs[] }`
  → `SynthesiseResult { suggestion }`
- `ActInput { incidentId, suggestion }` → `ActResult { actionRef }`

**LLM client interface** — agents code against this, never a concrete SDK.
- `LlmClient::complete(string $system, string $prompt): string`
- Ships with a `FakeLlmClient` (deterministic, keyed responses) bound in tests and in
  the demo by default. A real binding is a one-line swap, out of scope to wire.

**Workflow skeleton** (Arm B) — the `yield` points, stubbed:
```php
class TriageWorkflow extends Workflow
{
    public function execute(int $incidentId)
    {
        // fan-out enrichment
        [$class, $corr, $runbook] = yield all([
            activity(ClassifyActivity::class, $incidentId),
            activity(CorrelateActivity::class, $incidentId),
            activity(RunbookActivity::class, $incidentId),
        ]);

        $suggestion = yield activity(SynthesiseActivity::class, /* … */);

        // human gate: signal vs timer race  (confirm exact signal/timer API in docs)
        // approved = yield Workflow::awaitWithTimeout(<duration>, fn () => $this->approved)
        // on timeout -> escalate; on approve -> act

        if ($approved) {
            yield activity(ActActivity::class, $incidentId, $suggestion);
        } else {
            yield activity(EscalateActivity::class, $incidentId);
        }
    }
}
```
> The signal/timer method names above are illustrative. **Open question O-1**: confirm
> the exact v1 signal + `awaitWithTimeout` (or equivalent) API against the docs and the
> sample app's travel-agent saga before freezing this contract.

---

## 6. Requirements

### Must-Have (P0)
- **P0-1 Fan-out enrichment.** Classify, Correlate, Runbook run concurrently via
  `yield all([...])` and join before synthesis.
- **P0-2 Selective retry.** A forced failure in a *later* activity retries only that
  activity; earlier activities do not re-execute (assert via call-count on the fake
  LLM client).
- **P0-3 Crash-resume.** Killing the worker mid-workflow and restarting it resumes
  the run to completion with no duplicated activity side effects.
- **P0-4 Human gate with timeout.** Workflow suspends awaiting an approve/reject
  signal; if no signal arrives within the configured duration, it escalates instead.
- **P0-5 Naive foil.** Arm A implements the same pipeline as a job chain and is
  triggerable for side-by-side comparison.
- **P0-6 Demo harness.** Artisan commands trigger each arm; a fixture seeds a fake
  incident; a `--fail-at=N` toggle forces a deterministic failure for the retry/resume
  demos.

### Should-Have (P1)
- **P1-1** Waterline walkthrough: each P0 behaviour has a named workflow run visible
  in Waterline, referenced in the README.
- **P1-2** A short `CONTRAST.md` capturing, per behaviour, the line count / moving
  parts each arm needed (the foil's hand-rolled idempotency guards, the scheduled
  re-check, etc.).

### Could-Have (P2 — design for, don't build)
- **P2-1** Real LLM binding behind the same `LlmClient` interface.
- **P2-2** A second concurrent incident to show isolation between runs.
- **P2-3** Swap the domain shell to career-ops scoring to prove the architecture is
  domain-agnostic.
- **P2-4** Port the durable arm (Arm B) to **Temporal** — keeping the domain logic
  fixed and swapping only the orchestration substrate, to feel the
  convenience-vs-fidelity tradeoff firsthand. Full sketch in **Appendix A**. This is
  the highest-value stretch: it turns the spec's contract boundary into a live
  demonstration of what an abstraction buys you, and puts a production-grade
  orchestrator name (the one that appears in job specs) in your hands rather than just
  your notes.

---

## 7. Acceptance Criteria

- **AC-1 (P0-2)** Given a triage run, when `SynthesiseActivity` is forced to fail once
  via `--fail-at`, then it retries and succeeds, and the fake LLM client records
  exactly one call each for Classify/Correlate/Runbook (no re-execution).
- **AC-2 (P0-3)** Given a run suspended after fan-out, when the worker is killed and
  restarted, then the run completes and `ActActivity` (or `Escalate`) executes exactly
  once.
- **AC-3 (P0-4)** Given a run awaiting approval, when an approve signal is sent, then
  it proceeds to act; and given no signal within the timeout, then it escalates — both
  paths visible as distinct Waterline timelines.
- **AC-4 (P0-1)** Given a run, when it reaches enrichment, then the three activities
  start before any of them completes (assert overlap, not strict ordering).
- **AC-5 (P0-5)** Given the naive arm, when the equivalent of AC-1's failure occurs
  without hand-rolled guards, then a duplicated side effect is observable — documented
  in `CONTRAST.md` as the reason the guards are needed.

---

## 8. Parallel Work Decomposition

Dependency-gated. The serial spine produces Section 5 (Shared Contracts); only then
does the parallel burst begin; integration is serial again.

```
        ┌────────────────────────────────────────────┐
PHASE 0 │ SERIAL SPINE (you / one agent)              │
        │  scaffold • install • queue+migrations       │
        │  • Waterline • FREEZE Shared Contracts (§5)  │
        └───────────────────────┬──────────────────────┘
                                 │ contracts committed
        ┌────────────────────────┼──────────────────────┐
PHASE 1 │            PARALLEL BURST (fan out, 1 worktree each)
        │  Agent 1: Classify + Correlate + Runbook activities + tests
        │  Agent 2: Synthesise + signal gate + timer/escalate + tests
        │  Agent 3: Arm A naive job chain + tests + CONTRAST.md draft
        │  Agent 4: Harness (artisan cmds, fixture, --fail-at, resume script) + README
        └───────────────────────┬──────────────────────┘
                                 │ slices merged
        ┌────────────────────────┼──────────────────────┐
PHASE 2 │ SERIAL INTEGRATION (you)                      │
        │  wire arms • run 4 demos • capture Waterline   │
        │  • write the tool-evaluation notes             │
        └───────────────────────────────────────────────┘
```

**Why this shape:** parallel-agent tooling earns its keep only in Phase 1, where the
four slices touch non-overlapping files behind frozen interfaces. Using it in Phase 0
or Phase 2 just manufactures merge conflicts. If a slice keeps colliding with another,
that is a signal the contract in §5 was under-specified — fix the spec, not the merge.

---

## 9. Tech Stack & Constraints

- PHP 8.2+, Laravel 12 or 13.
- `laravel-workflow/laravel-workflow` (stable v1) + `laravel-workflow/waterline`.
- Queue driver: `database` for a zero-extra-dependency local start; Redis is a trivial
  upgrade and more realistic, but not required for the POC.
- A running queue worker is mandatory — workflows are dispatched repeatedly during
  replay; activities execute once.
- **Determinism rule:** workflow code (the `execute()` body) must avoid `rand()`,
  `now()`, and other non-deterministic calls; anything non-deterministic belongs
  inside an activity. Bake this into the agent task files.
- **Idempotency rule:** activities with external side effects must be safe to run
  twice (the framework retries on ambiguous failure). The fakes are trivially
  idempotent; document the real-world caveat.

---

## 10. Open Questions

- **O-1 (eng, blocking §5):** Exact v1 signal + await-with-timeout API. Resolve against
  docs + the sample app's travel-agent saga before freezing the workflow contract.
- **O-2 (eng, non-blocking):** Database vs Redis queue for the resume demo — does the
  `database` driver resume as cleanly under a hard `kill -9`? Verify early; fall back
  to Redis if not.
- **O-3 (process, non-blocking):** Does freezing §5 actually prevent agent collisions,
  or do the slices still touch shared files? This is itself a finding for the tool
  evaluation.

---

## 11. Out of Scope / Parking Lot

Real paging/Slack integration · retry backoff tuning · multi-tenant isolation ·
observability beyond Waterline · the 2.0 standalone-server build · model evaluation ·
anything that defends Arm A as a real design (it exists only to lose the comparison).

---

## Appendix A — Temporal Port Sketch *(P2-4)*

The thesis of this port: **the domain logic moves intact; only the substrate changes.**
If the contracts in §5 were drawn well, that claim holds — and watching exactly which
files you do and don't touch *is* the lesson. This is a sketch to design toward, not a
build-now task. Confirm exact `ActivityOptions`/timeout config against the Temporal PHP
SDK docs when you actually do it.

### What survives unchanged (the payoff)
- `Incident` domain model.
- `LlmClient` interface + `FakeLlmClient` — the activities still call this.
- The **bodies** of every activity (the logic that calls `LlmClient`). They move in
  essentially as-is.
- The activity I/O DTOs. (Temporal also recommends single-object params, so the shape
  matches.)
- The determinism + idempotency discipline. Same rules — Temporal just *enforces* them
  harder (runtime nondeterminism checks, `Workflow::getVersion` for versioning).

### What changes (the substrate)

| Layer | Durable Workflow v1 | Temporal |
|------|---------------------|----------|
| Infra | `queue:work` on your DB queue | gRPC ext + RoadRunner + a running Temporal Service (`temporal server start-dev` or Cloud) + a Laravel bridge (`keepsuit/laravel-temporal`) |
| Monitor | Waterline (`/waterline`) | Temporal Web UI (dev server, ~`:8233`) |
| Activity decl. | `class X extends Activity` | `#[ActivityInterface]` + `#[ActivityMethod]` on an interface; impl implements it |
| Workflow decl. | `class W extends Workflow`, `yield activity(X::class, …)` | `#[WorkflowInterface]`/`#[WorkflowMethod]`; calls via `Workflow::newActivityStub(...)` |
| Retry/timeout | framework defaults | declarative on `ActivityOptions` (`withStartToCloseTimeout`, `RetryOptions->withMaximumAttempts(...)`) — your selective-retry P0 becomes config |
| Dispatch | `WorkflowStub::make(W::class)->start($id)` | `WorkflowClient->newWorkflowStub(WInterface::class)` then start |
| Worker | Laravel queue worker | RoadRunner worker registering workflow + activity types on a task queue |

### The three deltas that carry the weight

**1. Fan-out** (P0-1) — `yield all([...])` becomes promise-based:
```php
$class   = $this->stub->classify($incidentId);     // returns a promise
$corr    = $this->stub->correlate($incidentId);
$runbook = $this->stub->runbook($incidentId);
[$c, $r, $b] = yield Promise::all([$class, $corr, $runbook]);
```

**2. Human gate** (P0-4) — v1's signal/await becomes a first-class signal racing a
timer, which is arguably *cleaner* than v1:
```php
#[SignalMethod]
public function approve(): void { $this->approved = true; }

#[WorkflowMethod]
public function run(int $incidentId) {
    // …enrich, synthesise…
    // proceeds on approve, or escalates when the interval elapses
    $approved = yield Workflow::awaitWithTimeout($timeout, fn () => $this->approved);
    // $approved === false  ⇒  timed out  ⇒  escalate
}
```

**3. Selective retry** (P0-2) — in v1 you assert it via fake-LLM call counts; in
Temporal it's *declared* on the activity stub and enforced by the service:
```php
$this->stub = Workflow::newActivityStub(
    TriageActivitiesInterface::class,
    ActivityOptions::new()
        ->withStartToCloseTimeout(CarbonInterval::seconds(30))
        ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(5)),
);
```

### What you'll have learned
That the contract boundary was real — domain logic ported with near-zero edits — while
the runtime, dispatch, and infra are irreducibly substrate-specific. And you'll have
felt Temporal's production-fidelity gap concretely: declarative retry/timeout policies,
enforced determinism, versioning, and an orchestrator that survives your app process
dying entirely — the things you pay the RoadRunner/gRPC setup tax to get.