# Extension & Contribution Guide — Symfony Engineering Agent

This guide outlines the formal procedure for authoring new Agents and Skills, extending the ecosystem with governance, avoiding fragmentation, and maintaining architectural consistency.

---

## 1. Expansion Principles

Before creating a new file, apply this decision filter:
1. **Anti-Fragmentation Rule:** Can this capability be served by an existing agent by adding a Skill? (If yes, do NOT create a new agent; create a Skill).
2. **Strict Scope:** Agents and skills must strictly target PHP 8.2+ and Symfony 6.4 LTS / 7.x+.
3. **No Redundancy:** Never create agents or skills that duplicate existing capabilities.

---

## 2. How to Create a New Skill

A Skill is an actionable technical procedure with prescriptive rules, conventions, and concrete examples of correct and incorrect code.

### Step 1: Create Directory and File
Create the directory in `skills/<technical-id>/` and the required `SKILL.md` file:
```bash
mkdir -p skills/domain-events
touch skills/domain-events/SKILL.md
```

### Step 2: Mandatory 11-Section Structure
The `SKILL.md` file **must** include the exact 11 standardized sections:

```markdown
# Skill: Skill Name

## 1. Objective and Responsibility
Define in 1-2 paragraphs the exact purpose of the skill and the problem it solves.

## 2. Scope and Limitations
What this skill covers and what it explicitly does NOT cover.

## 3. Required Technical Knowledge
Specific PHP and Symfony component knowledge required.

## 4. Development Standards and Conventions
Naming conventions, strict typing (`declare(strict_types=1);`), immutability.

## 5. Architectural Rules and Code Organization
Directory locations within `src/`, namespace patterns, and layer separation.

## 6. PHP and Symfony Best Practices
Recommended practices for the stable version in use (PHP 8.2+, Symfony 6.4/7+).

## 7. Examples of Correct and Incorrect Implementations
Concrete code snippets comparing correct modern code vs anti-patterns.

## 8. Technical Decision Criteria
Objective criteria for choosing technical approaches (e.g., when to use X vs Y).

## 9. Validation and Testing Procedures
Test execution procedures (`WebTestCase`, `KernelTestCase`) and static checks.

## 10. Error Handling and Exceptions
Domain exception classes, HTTP status codes, and RFC 7807 Problem Details.

## 11. Objective Quality and Completion Criteria
Factual checklist required to declare a task using this skill as complete.
```

---

## 3. How to Create a New Agent

An Agent is a specialized software engineering role with a defined mission, operational capabilities, and strict delegation boundaries.

### Step 1: Create the Agent File
Create the file in `agents/<technical-id>.md`:
```bash
touch agents/domain-event-specialist.md
```

### Step 2: Mandatory 6-Section Structure
The agent file **must** include the exact 6 standardized sections:

```markdown
# Agent: Specialist Name

## 1. Primary Responsibility
Core mission of the agent and its system impact.

## 2. Specialized Technical Knowledge
Technologies, components, and concepts the agent specializes in.

## 3. Associated Skills
List of skills this agent can load and execute:
- `skills/domain-events`
- `skills/symfony-application`
- `skills/testing-quality`

## 4. Operational Capabilities
- **Create and modify files:** Directory scopes where the agent operates.
- **Implement flows:** What types of business or infrastructure logic it builds.
- **Run diagnostics:** What local verification tools it executes.

## 5. Delegation Criteria
Strict rules defining when the agent MUST hand off work to other specialists:
- For database persistence: delegate to `agents/database-engineer.md`.
- For async transport: delegate to `agents/symfony-performance-specialist.md`.

## 6. Handoff & Deliverables
Tangible artifacts delivered by the agent upon task completion.
```

---

## 4. Ecosystem Registration

After creating the Agent and/or Skill:
1. **Register in `AGENTS.md`:** Add the new agent to the specialty matrix and role list.
2. **Register in `CATALOG.md`:** Add the new component to the resource catalog.
3. **Update `README.md`:** Add the agent to the Agent Catalog and the [Relationship Matrix](README.md#6-relationship-matrix-agents-x-skills).
4. **Verify Multi-Platform Adapters:** Update Cursor (`.cursor/rules/`), Claude (`CLAUDE.md`), and Copilot (`.github/copilot-instructions.md`).

---

## 5. Component Validation Checklist

- [ ] Does the file strictly conform to the required sections?
- [ ] Does the component exclusively focus on PHP 8.2+ and Symfony 6.4/7+?
- [ ] Do all referenced file paths exist in the repository?
- [ ] Do code examples feature `declare(strict_types=1);` and modern attributes?
- [ ] Is there zero overlap with existing agents or skills?
