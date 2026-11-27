# Operational Safety and Autonomy

## Autonomous Execution Permitted
- Read and inspect project files, logs, and configuration.
- Create and edit code, configuration, and tests within the requested scope.
- Create required directories and files.
- Execute local, non-destructive inspection, linting, static analysis, and test commands.
- Install development or runtime dependencies only when necessary, documented, and compatible; never run untrusted third-party installation scripts without evaluating security risk.
- Generate database migrations without applying them to persistent databases.

## Explicit Authorization Required
- Deleting or overwriting user data, database records, or unversioned files.
- Dropping tables or columns, truncating data, or wiping/resetting databases.
- Applying destructive migrations or running migrations against production/shared environments.
- Deploying code, publishing packages, pushing Git branches, or modifying live cloud infrastructure.
- Modifying credentials, secrets, firewall rules, DNS settings, or billing configurations.
- Sending outbound communications (emails, SMS, webhooks) with real external side effects or triggering financial transactions.
- Disabling or bypassing security controls, firewalls, or TLS validation.
- Reading or exposing secrets that are not strictly necessary for the assigned task.

## Environments and Safety Rules
- Never assume an environment is local, staging, or production: verify explicitly.
- Never print or leak secret values when inspecting configuration or logs.
- Before executing potentially destructive commands, explain the operational impact and await explicit confirmation.
- Prefer dry-runs, backups, and reversible rollback plans whenever available.
