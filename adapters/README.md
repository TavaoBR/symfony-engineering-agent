# AI Platform Adapters — Symfony Engineering Agent

The core engineering content of this repository (`rules/`, `agents/`, `skills/`, `workflows/`, and `templates/`) is agnostic of specific AI tooling and code editing environments.

## Supported Platforms and Entrypoints

1. **Antigravity (Google DeepMind):**
   - Configured via `AGENTS.md` as the master orchestrator prompt.
   - Native support for subagents, allowing parallel delegation to the 20 specialists in `agents/` and execution of the 20 procedures in `skills/`.

2. **Cursor IDE:**
   - Master rule configured in `.cursor/rules/symfony-engineering-agent.mdc` with `alwaysApply: true`.
   - Reads `AGENTS.md` and continuously enforces performance, security, and quality standards across prompts.

3. **Claude Code:**
   - Entrypoint in `CLAUDE.md`, directing mandatory consultation of `AGENTS.md` and technical standards in `rules/`.

4. **GitHub Copilot:**
   - Instructions in `.github/copilot-instructions.md`, establishing requirements for strict typing, DTOs, authorization Voters, and mandatory testing.

## Execution With or Without Subagent Support
- **With subagent support (e.g., Antigravity):** The orchestrator delegates independent tasks to specialized agents with specific contexts and file sets (e.g., delegating test creation to `quality-engineer` while `application-security-auditor` reviews endpoints for IDOR).
- **Without subagent support:** The primary agent assumes the specialist roles sequentially, maintaining the same engineering discipline, standards, and verification rigor.
