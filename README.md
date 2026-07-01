# Durable Triage POC

A side-by-side comparison of **naive queued-job orchestration** (Arm A) vs **durable workflow orchestration** (Arm B) for a multi-step LLM incident-triage pipeline.

See [SPEC.md](SPEC.md) for the full contract and acceptance criteria.

## Setup

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
touch database/database.sqlite   # if using SQLite
php artisan migrate
```

Start a queue worker in a dedicated terminal (workflows require it):

```bash
php artisan queue:work
```

Optional: use the dev script to run server + queue + Vite together:

```bash
composer dev
```

## Demo harness

All triage commands assume a running queue worker.

| Command | Purpose |
|---------|---------|
| `php artisan triage:seed` | Seed a fake incident |
| `php artisan triage:durable {incidentId}` | Start Arm B (`TriageWorkflow`) |
| `php artisan triage:naive {incidentId}` | Start Arm A (naive job chain) |
| `php artisan triage:approve {workflowId}` | Send approve signal to a suspended workflow |

### Walkthrough — durable arm

```bash
# Terminal 1
php artisan queue:work

# Terminal 2
php artisan triage:seed
php artisan triage:durable 1
# Note the workflow ID printed in the output

# When the workflow reaches the human gate, approve it:
php artisan triage:approve {workflowId}
```

Open **Waterline** at [/waterline](http://localhost:8000/waterline) to inspect the run timeline, activity fan-out, retries, and signal events.

### Walkthrough — naive arm

```bash
php artisan triage:seed
php artisan triage:naive 1
```

Compare the same incident processed through the job chain. Side-effect duplication and hand-rolled idempotency differences are documented in `CONTRAST.md` (Agent 3).

## `--fail-at` demo (selective retry)

Force a deterministic LLM failure on the *Nth* `complete()` call to demonstrate selective retry (P0-2):

| Step | Activity |
|------|----------|
| 1 | Classify |
| 2 | Correlate |
| 3 | Runbook |
| 4 | Synthesise |
| 5 | Act |

```bash
php artisan triage:durable 1 --fail-at=4
```

The fake LLM client throws on the 4th call (synthesise). The workflow retries only that activity; classify/correlate/runbook call counts stay at 1. Verify via `FakeLlmClient::getCallCount('classify')` in tests or Waterline activity logs.

## Crash-resume demo

1. Start a durable run: `php artisan triage:durable 1`
2. Kill the queue worker mid-flight (`Ctrl+C` or `kill`).
3. Restart the worker:

   **Unix:** `./scripts/resume-worker.sh`

   **Windows:** `.\scripts\resume-worker.ps1`

4. The workflow resumes from its event stream without re-executing completed activities.

## Waterline

Workflow runs are visible at **`/waterline`** once the app is served (`php artisan serve` or `composer dev`). Use it to verify:

- Fan-out overlap (classify + correlate + runbook start concurrently)
- Selective retry after `--fail-at`
- Approve vs timeout-escalate timelines

## P0 acceptance criteria (overview)

| ID | Behaviour | How to demo |
|----|-----------|-------------|
| P0-1 | Fan-out enrichment | Waterline shows three enrichment activities overlapping |
| P0-2 | Selective retry | `--fail-at=4`; earlier activities not re-run |
| P0-3 | Crash-resume | Kill worker, run resume script, run completes |
| P0-4 | Human gate + timeout | `triage:approve` vs waiting for timer escalation |
| P0-5 | Naive foil | `triage:naive` for side-by-side comparison |
| P0-6 | Demo harness | Commands above + fixture seeder |

## Architecture notes

- Activities call `LlmClient::complete()`; the demo binds `FakeLlmClient` (deterministic, keyed responses) in `AppServiceProvider`.
- Swap to a real LLM implementation by rebinding `LlmClient` — one line change.
- Workflow code must stay deterministic; non-determinism belongs inside activities.
