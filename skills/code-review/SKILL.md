---
name: code-review
description: Audit git diff for bugs, AI anti-patterns, strict typing, IDOR vulnerabilities, and N+1 queries.
---

# Skill: Critical Code and Pull Request Review (Code Review)

## 1. Objective and Responsibility
Perform independent, critical technical reviews of code diffs, pull requests, and new implementations in Symfony applications, assessing architectural adherence, data integrity, performance, OWASP security, test quality, and absence of regressions.

## 2. Scope and Limitations
- **Scope:** Reviewing `git diff` and surrounding context, identifying authorization vulnerabilities (IDOR), detecting N+1 queries, validating strict typing in PHP 8.2+, and flagging AI code anti-patterns.
- **Limitations:** Does not edit code directly during review (produces structured technical feedback reports).

## 3. Required Technical Knowledge
- Technical severity classification: Blocker, Major, Minor, Informational.
- AI code generation anti-patterns (per `rules/anti-ai-patterns.md`): overengineering, hollow boilerplate, error suppression, scenic tests.
- Symfony and Doctrine standards: granular voters, immutable DTOs, fetch joins, atomic transactions.

## 4. Development Standards and Conventions
- Evaluate code against the mandatory priority hierarchy:
  1. **Bugs and Regressions:** Logic flaws, broken contracts, unhandled exceptions.
  2. **Security and Authorization:** Exposed routes lacking Voters, OWASP vulnerabilities, leaked secrets.
  3. **Data Integrity and Concurrency:** Missing transactions, race conditions in balances/inventory, missing `onDelete` foreign key actions.
  4. **Performance and Queries:** N+1 queries in loops, unpaginated collections, slow queries.
  5. **Typing and Stack Standards:** Missing `declare(strict_types=1);`, `mixed` usage, business logic in controllers.
  6. **Test Quality:** Weak assertions, missing negative test cases (422, 403).

## 5. Architectural Rules and Code Organization
- Verify that new classes reside in proper directories (`src/DTO/`, `src/Service/`, `src/Repository/`).
- Verify that no circular dependencies or improper domain-to-infrastructure coupling were introduced.

## 6. PHP and Symfony Best Practices
- **Detecting N+1 Queries in Diffs:** Check if any service or controller iterates over an entity collection and calls a relationship getter (e.g., `$item->getProduct()->getTitle()`) without the query having called `addSelect()`.
- **Verifying Authorization on Endpoints:** Verify that endpoints accepting `{id}` call `$this->denyAccessUnlessGranted(...)` with a resource Voter.

## 7. Examples of Correct and Incorrect Implementations

### Example: Technical Code Review Assessment
**Incorrect (Vague, superficial feedback):**
```text
Incorrect: "Looks great! Good job. Maybe clean up the types, but overall approved."
Problem: Failed to catch real security flaws, ignored N+1 queries, approved broken code.
```

**Correct (Structured technical report with file, line, risk, and concrete remediation):**
```text
Code Review Result: REJECTED WITH BLOCKING ISSUES

[BLOCKER] Security Vulnerability (IDOR) - src/Controller/Api/InvoiceController.php:28
- Code: $invoice = $this->repository->find($id); return $this->json($invoice);
- Risk: Any authenticated user can view other users' invoices by guessing the ID.
- Mandatory Fix: Call $this->denyAccessUnlessGranted(InvoiceVoter::VIEW, $invoice).

[BLOCKER] Performance / N+1 Query - src/Repository/OrderRepository.php:45
- Code: $qb->select('o')->from(Order::class, 'o');
- Risk: Serializing orders triggers individual SQL queries for each order's customer and items.
- Mandatory Fix: Add ->addSelect('c', 'i')->innerJoin('o.customer', 'c')->leftJoin('o.items', 'i').

[MINOR] Strict Typing - src/DTO/CreateOrderDTO.php:1
- Missing declare(strict_types=1); at the top of the file.
```

## 8. Technical Decision Criteria
- **Approve:** Zero Blocker and Major issues; passing test suite and full compliance with repository standards.
- **Reject:** One or more Blocker issues identified (security, data integrity, or major functional defect).

## 9. Validation and Testing Procedures
- Inspect the complete diff:
  ```bash
  git diff HEAD~1
  ```
- Verify that the test suite passes on the reviewed branch:
  ```bash
  php bin/phpunit && ./vendor/bin/phpstan analyse -l 8 src
  ```

## 10. Error Handling and Exceptions
- If a test fails during diff review, report the failure as a Blocker accompanied by PHPUnit output.

## 11. Objective Quality and Completion Criteria
- Structured review emitted detailing files, lines, and severities.
- All 6 priority criteria evaluated and documented.
