# Catalog of Skills and Agents — Symfony Engineering Agent

## Skills (20 Specialized Procedures)

### Core Engineering & Architecture
- `project-discovery` — Factual project analysis (versions, bundles, routes, conventions).
- `project-bootstrap` — Official Symfony project initialization from scratch (CLI, skeleton, Flex recipes, tests, README).
- `architecture-design` — Architecture design (domain boundaries, Clean/Hexagonal, DTOs, ADRs).
- `symfony-application` — Application feature development (Controllers, DTOs, Services, Messenger, Commands).
- `doctrine-persistence` — Persistence with Doctrine ORM/DBAL (entities, relationships, repositories, migrations).
- `api-design` — HTTP API design and implementation (RESTful, DTOs, MapRequestPayload, RFC 7807/9457).
- `testing-quality` — Automated testing and code quality (PHPUnit, WebTestCase, PHPStan, PHP-CS-Fixer).
- `external-api-integration` — External API and webhook integration (HttpClient, resilience, HMAC-SHA256 signatures).
- `debugging` — Root-cause bug investigation and automated regression test creation.
- `safe-refactoring` — Safe refactoring and modernization to PHP 8.2+ (Rector, attributes, characterization tests).
- `code-review` — Independent code and Pull Request review (severity matrix, anti-ai-patterns).
- `technical-documentation` — Engineering documentation (README, ARCHITECTURE.md, ADRs in DECISIONS.md).

### Security Nucleus
- `symfony-security` — Symfony Security framework implementation (Firewalls, Authenticators, granular Voters, CSRF, RateLimiter).
- `secure-coding` — PHP secure coding practices (OWASP Top 10, sanitization, SSRF guards, Path Traversal, secrets).
- `security-audit` — Application security auditing (endpoint matrix, IDOR/BOLA verification, tenant isolation, CVSS).
- `security-review` — End-to-end security governance and review for Symfony applications.

### Performance Nucleus
- `performance-profiling` — Performance profiling and diagnosis (Symfony Profiler, P95 latency, memory footprints, flamegraphs).
- `symfony-optimization` — Symfony framework optimization (DI Container, private services, asynchronous Messenger, HTTP Cache).
- `database-performance` — Database and Doctrine optimization (N+1 query elimination, Fetch Joins, DTO projections, indexes, EXPLAIN).
- `php-optimization` — PHP runtime optimization (Big-O complexity, Generators, streaming, OPcache preloading, memory).

---

## Agents (20 Engineering Specialists)

### Core Engineering & Architecture
- `orchestrator` — Engineering Orchestrator: Classify requests, coordinate specialists, and ensure evidence-based delivery.
- `project-analyst` — Project Analyst: Non-destructively map repository, versions, bundles, architecture, and technical risks.
- `project-bootstrapper` — Project Bootstrapper: Initialize official Symfony projects from scratch with clean, testable foundations.
- `symfony-architect` — Symfony Architect: Define architectural boundaries, modularization, interfaces, and documented ADRs.
- `symfony-backend-developer` — Symfony Backend Developer: Implement controllers, DTOs, services, commands, and domain logic.
- `database-engineer` — Database & Doctrine Engineer: Model entities, relationships, repositories, and reversible migrations.
- `quality-engineer` — Quality Engineer: Design and execute automated test suites (unit, integration, functional) and static analysis.
- `integration-specialist` — Integration Specialist: Integrate external APIs, resilient HTTP clients, SDKs, and secure webhooks.
- `debugger` — Debugger Specialist: Investigate root causes and ensure bug fixes are locked with regression tests.
- `refactoring-specialist` — Refactoring Specialist: Modernize legacy code to PHP 8.2+ and eliminate technical debt safely.
- `code-reviewer` — Code Reviewer: Independently review code diffs against bugs, security vulnerabilities, and quality standards.
- `technical-writer` — Technical Writer: Produce reproducible, accurate documentation reflecting actual code state.

### Security Nucleus
- `security-engineer` — Security Engineer: Threat modeling (STRIDE), security architecture governance, and cryptographic policies.
- `symfony-security-specialist` — Symfony Security Specialist: Configure Firewalls, Authenticators, granular Voters, CSRF, and RateLimiter.
- `php-security-specialist` — PHP Security Specialist: Guard against OWASP vulnerabilities (SQLi, XSS, SSRF, Command Injection, Path Traversal).
- `application-security-auditor` — Application Security Auditor: Audit endpoints, verify IDOR/BOLA, and generate CVSS risk reports.

### Performance Nucleus
- `performance-engineer` — Performance Engineer: Holistic profiling, CPU/memory bottleneck identification, and performance budgets.
- `symfony-performance-specialist` — Symfony Performance Specialist: Optimize DI container compilation, EventDispatcher, Messenger, and Cache.
- `database-performance-specialist` — Database Performance Specialist: Optimize SQL queries, eliminate N+1 queries, indexes, and EXPLAIN plans.
- `php-performance-specialist` — PHP Performance Specialist: Optimize Big-O algorithms, data structures, streams, and Generators.

---

## CI/CD Validation Gate Templates

- `templates/ci/github/symfony-ci.yml` — GitHub Actions workflow for Pull Requests: lint, security audit, PHPStan (level 8+), PostgreSQL service, and PHPUnit matrix (PHP 8.2 & 8.3).
- `templates/ci/gitlab/.gitlab-ci.yml` — GitLab CI/CD pipeline template with stages: validate, analyze, security, and test.
- `docs/CI_CD_GATES.md` — Comprehensive implementation guide and branch protection instructions.

---

## Automated Profiler & Benchmark Templates

- `templates/benchmarks/SymfonyProfilerBenchmarkTrait.php` — Reusable trait for `WebTestCase` functional tests asserting latency budgets, peak memory, and zero N+1 duplicate queries.
- `templates/benchmarks/benchmark-runner.php` — Standalone CLI benchmark runner generating P50/P95 latency percentiles, memory metrics, and Markdown reports.
- `templates/benchmarks/benchmark.json.dist` — Endpoint benchmark target configuration and performance budget definitions.
- `docs/PROFILER_BENCHMARKS.md` — Automated profiling and benchmark manual.

---

## OpenAPI & Swagger Contract Testing Templates

- `templates/contracts/OpenApiContractTestTrait.php` — Reusable trait for `WebTestCase` asserting response status codes, headers, and JSON body schemas against OpenAPI 3.0 / 3.1 specifications.
- `templates/contracts/openapi-validator.php` — Standalone CLI validator verifying OpenAPI syntax, broken `$ref` pointers, RFC 7807 Problem Details compliance, and Symfony router coverage.
- `templates/contracts/openapi.yaml.dist` — Reference OpenAPI 3.1 contract specification in YAML format with strict typing and Problem Details error responses.
- `templates/contracts/openapi.json.dist` — Reference OpenAPI 3.1 contract specification in native JSON format.
- `docs/OPENAPI_CONTRACT_TESTING.md` — Comprehensive OpenAPI contract testing and schema regression manual.

---

## Mutation Testing & Architectural Boundary Templates

- `templates/testing/infection.json.dist` — Reference Infection PHP mutation testing configuration with default mutator profiles and MSI budgets (70% MSI / 80% Covered MSI).
- `templates/testing/mutation-runner.php` — Standalone CLI runner executing mutation testing, evaluating MSI thresholds, and generating Markdown reports.
- `templates/architecture/deptrac.yaml.dist` — Reference Deptrac 1.x/2.x configuration enforcing Hexagonal Architecture layer boundaries (Domain, Application, Infrastructure, Presentation, DTO).
- `templates/architecture/architecture-validator.php` — Standalone CLI validator running official Deptrac or built-in static layer boundary scanner.
- `docs/MUTATION_TESTING.md` — Comprehensive mutation testing and test efficacy guide with Infection PHP.
- `docs/ARCHITECTURAL_BOUNDARIES.md` — Comprehensive architectural boundary enforcement manual with Deptrac.

---

## Load Testing & Asynchronous Messenger Scaling Templates

- `templates/load-testing/k6-load-test.js` — Reference k6 load and stress testing script with baseline and spike traffic scenarios and SLA threshold assertions.
- `templates/load-testing/k6-runner.php` — Standalone CLI runner executing k6 or concurrent curl_multi load testing engine, evaluating P95/P99 latency and error rates.
- `templates/messenger/messenger.yaml.dist` — Production-grade Symfony Messenger configuration template featuring tiered transports, exponential backoff, and dead-letter queue (DLQ).
- `templates/messenger/messenger-worker-verifier.php` — Standalone CLI verifier auditing queue routing, retry policies, and worker health.
- `docs/LOAD_AND_STRESS_TESTING.md` — Comprehensive load and stress testing manual with k6 and concurrency tuning.
- `docs/ASYNC_MESSENGER_SCALING.md` — Comprehensive asynchronous worker scaling and queue verification manual with Symfony Messenger.

---

## Static Application Security Testing (SAST) & CVE Remediation Templates

- `templates/security/semgrep.yaml.dist` — Custom Semgrep ruleset for PHP 8.2+ and Symfony 6.4/7+ detecting SQL Injection, insecure deserialization, command execution, path traversal, open redirects, and hardcoded secrets.
- `templates/security/sast-scanner.php` — Standalone CLI SAST security scanner executing Semgrep or fallback static AST analysis, evaluating vulnerability severity thresholds.
- `templates/security/cve-remediator.php` — Standalone CLI dependency auditor parsing `composer audit`, generating structured vulnerability reports, and emitting atomic remediation update commands.
- `workflows/cve-remediation.md` — 5-phase operational workflow coordinating triage, blast radius analysis, atomic update, and verification gates.
- `docs/SAST_AND_CVE_REMEDIATION.md` — Comprehensive SAST security scanning and automated CVE remediation manual.

---

## Containerization & Production Docker Templates

- `templates/docker/Dockerfile.frankenphp` — Multi-stage Dockerfile for Symfony 6.4/7+ on PHP 8.2/8.3 using FrankenPHP with Worker Mode, Caddy, HTTP/3 QUIC, and non-root execution.
- `templates/docker/Dockerfile.php-fpm` — Multi-stage Dockerfile for traditional Nginx and PHP-FPM architectures.
- `templates/docker/Caddyfile.dist` — Production-tuned Caddyfile configuration featuring FrankenPHP worker mode, modern compression (Zstandard/Gzip), and strict HTTP security headers.
- `templates/docker/compose.yaml.dist` — Production-grade Docker Compose orchestration defining FrankenPHP web/app, PostgreSQL 16, Redis 7, and Symfony Messenger async worker.
- `templates/docker/docker-entrypoint.sh` — POSIX-compliant container entrypoint script handling database connectivity polling, automatic migrations, and production cache warmup.
- `templates/docker/docker-validator.php` — Standalone CLI validator auditing multi-stage targets, non-root user directives, healthchecks, Compose dependencies, and security headers.
- `workflows/containerization-deployment.md` — 5-phase operational workflow coordinating container architecture selection, worker tuning, security hardening, and deployment signoff.
- `docs/DOCKER_CONTAINERIZATION.md` — Comprehensive manual for FrankenPHP containerization, worker mode tuning, and production orchestration.

---

## Kubernetes Helm & GitOps Orchestration Templates

- `templates/kubernetes/helm/symfony-app/` — Production-grade cloud-native Helm chart for Symfony 6.4/7+ applications.
- `templates/kubernetes/helm/symfony-app/Chart.yaml` — Official Helm v2 metadata with semantic versioning.
- `templates/kubernetes/helm/symfony-app/values.yaml` — Production values defining replica quotas, resources, HPA thresholds, security contexts, probes, and worker parameters.
- `templates/kubernetes/helm/symfony-app/templates/deployment-web.yaml` — Zero-downtime rolling update deployment for the web application (`maxUnavailable: 0`).
- `templates/kubernetes/helm/symfony-app/templates/deployment-worker.yaml` — Dedicated deployment for asynchronous Symfony Messenger background workers.
- `templates/kubernetes/helm/symfony-app/templates/hpa.yaml` — Native `autoscaling/v2` Horizontal Pod Autoscaler for web and worker workloads.
- `templates/kubernetes/helm/symfony-app/templates/pdb.yaml` — `policy/v1` PodDisruptionBudget ensuring high availability (`minAvailable: 1`).
- `templates/kubernetes/helm/symfony-app/templates/job-migration.yaml` — Pre-upgrade/post-install database migration Job with Helm hook annotations.
- `templates/kubernetes/gitops/argocd-application.yaml.dist` — Production-ready ArgoCD Application manifest with automated prune and self-heal sync policies.
- `templates/kubernetes/gitops/flux-kustomization.yaml.dist` — Flux CD GitRepository and HelmRelease continuous delivery manifests.
- `templates/kubernetes/k8s-validator.php` — Standalone CLI validator auditing Helm chart metadata, resource quotas, CIS non-root execution, HPA, and GitOps syntax.
- `workflows/kubernetes-gitops-deployment.md` — 5-phase operational workflow coordinating cluster architecture, HPA calibration, GitOps delivery, and release signoff.
- `docs/KUBERNETES_AND_GITOPS.md` — Comprehensive cloud-native manual for Kubernetes, Helm, HPA, and GitOps continuous delivery.

---

## Observability, OpenTelemetry & Metrics Templates

- `templates/observability/otel.yaml.dist` — OpenTelemetry PHP SDK/bundle configuration with ParentBased tracing sampler, OTLP exporters, and auto-instrumentation for HTTP, Doctrine, Messenger, and HttpClient.
- `templates/observability/prometheus.yaml.dist` — Prometheus metrics exporter specification defining HTTP RED metrics, Doctrine query histograms, Messenger queue gauges, and OPcache metrics.
- `templates/observability/otel-collector-config.yaml.dist` — OpenTelemetry Collector pipeline configuration with memory limiter, batch processors, and OTLP/Prometheus exporters.
- `templates/observability/grafana/symfony-dashboard.json` — Production-ready Grafana dashboard JSON featuring executive SLA panels, latency percentiles, error rates, and queue backlog monitors.
- `templates/observability/observability-validator.php` — Standalone CLI validator auditing OpenTelemetry tracing, Prometheus metrics, OTel Collector pipelines, and Grafana dashboard PromQL queries.
- `workflows/observability-telemetry-monitoring.md` — 5-phase operational workflow coordinating telemetry scaffolding, span enrichment, metrics export, and Grafana visualization.
- `docs/OBSERVABILITY_AND_TELEMETRY.md` — Comprehensive manual for OpenTelemetry distributed tracing, Prometheus metrics, and Grafana production observability.

---

## Enterprise Disaster Recovery (DR) & Failover Templates

- `templates/disaster-recovery/dr-policy.yaml.dist` — Disaster Recovery policy defining RTO (< 5m) and RPO (< 1m) budgets, multi-region active/hot standby topology, and automated failover triggers.
- `templates/disaster-recovery/doctrine-multiregion.yaml.dist` — Production Doctrine DBAL multi-region primary-replica connection configuration featuring automatic read/write splitting.
- `templates/disaster-recovery/backup-verification.sh` — Automated bash script for continuous backup restoration testing, cryptographic SHA256 checksum verification, and schema checks.
- `templates/disaster-recovery/dr-validator.php` — Standalone CLI validator auditing DR policies, RTO/RPO limits, Doctrine primary-replica pooling, and backup verification scripts.
- `workflows/disaster-recovery-failover.md` — 5-phase operational workflow coordinating DR policy definition, read/write splitting, restoration drills, failover simulation, and signoff.
- `docs/DISASTER_RECOVERY_AND_FAILOVER.md` — Comprehensive enterprise manual for Disaster Recovery, multi-region replication, and automated backup integrity testing.
