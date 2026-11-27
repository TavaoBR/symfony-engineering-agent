---
name: symfony
description: Invoke the Symfony Orchestrator to plan, coordinate, and execute end-to-end Symfony features or tasks.
---

# /symfony — Symfony Orchestrator Command

Act as the **Orchestrator** (`agents/orchestrator.md`) of the Symfony Engineering Agent.

## Objective
Coordinate the full engineering lifecycle for the user's request:
1. Translate the user's prompt into observable behavior and acceptance criteria.
2. Select and assemble the necessary specialist agents (`symfony-architect`, `symfony-backend-developer`, `database-engineer`, `database-performance-specialist`, `symfony-security-specialist`, `quality-engineer`).
3. Enforce strict typing (`declare(strict_types=1);`), skinny controllers, DTOs with `#[MapRequestPayload]`, Fetch Joins for N+1 prevention, and Voters for authorization.
4. Execute local validations (`php bin/console lint:container`, `vendor/bin/phpstan analyse`, `php bin/phpunit`).
5. Present the verified delivery report with execution logs.

## Autonomous Swarm Mode & Self-Healing CI (v3.0)
For large-scale or multi-module epics, the Orchestrator initiates autonomous swarm mode (`templates/swarm/swarm-topology.yaml.dist`):
1. **Dynamic Task Sharding:** Splits epics into a DAG of domain-driven or layer-driven slices (`php templates/swarm/swarm-runner.php`).
2. **Parallel Subagent Execution:** Dispatches concurrent specialist agents in isolated Git worktrees.
3. **Autonomous Self-Healing Loop:** Automatically catches failing quality gates and delegates atomic patches via `php templates/swarm/self-healing-gate.php`.
4. **Reference Manual:** See `docs/SWARM_ORCHESTRATION_AND_SELF_HEALING.md` and `workflows/swarm-orchestration-self-healing.md`.

$ARGUMENTS
