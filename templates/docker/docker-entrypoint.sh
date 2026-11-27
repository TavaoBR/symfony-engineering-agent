#!/usr/bin/env bash
# ==============================================================================
# Symfony Engineering Agent — Container Entrypoint Script
# Handles database readiness polling, automatic migrations, and cache warmup
# ==============================================================================
set -Eeuo pipefail

echo "=================================================================="
echo "  Symfony Engineering Agent — Initializing Container Environment  "
echo "=================================================================="

# Wait for relational database connectivity if DATABASE_URL is defined
if [ -n "${DATABASE_URL:-}" ] && [ -f "bin/console" ]; then
    echo "[entrypoint] Waiting for database connection..."
    MAX_ATTEMPTS=30
    ATTEMPT=1
    until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1 || [ $ATTEMPT -ge $MAX_ATTEMPTS ]; do
        echo "[entrypoint] Waiting for database to be ready (attempt $ATTEMPT/$MAX_ATTEMPTS)..."
        sleep 2
        ATTEMPT=$((ATTEMPT + 1))
    done

    if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
        echo "[entrypoint] Warning: Database connection timeout reached. Proceeding anyway."
    else
        echo "[entrypoint] Database connection established successfully!"
        
        # Execute database migrations if in production or explicitly requested
        if [ "${AUTO_MIGRATE:-false}" = "true" ] || [ "${APP_ENV:-}" = "prod" ]; then
            echo "[entrypoint] Executing database migrations..."
            php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
        fi
    fi
fi

# In production mode, ensure cache is warmed up
if [ "${APP_ENV:-}" = "prod" ] && [ -f "bin/console" ]; then
    echo "[entrypoint] Verifying production cache warmup..."
    php bin/console cache:warmup --no-debug || true
fi

echo "[entrypoint] Container initialization complete. Executing: $@"
exec "$@"
