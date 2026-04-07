#!/bin/bash
set -e
set -o pipefail  # Ensures that pipeline failures are caught

# ========================================================
# Script: wait-for-healthy.sh
# Purpose: This script waits for Docker containers in a
#          Docker Compose environment to become healthy.
#          It checks the health status of services defined
#          in the Docker Compose file that has a healthcheck
#          defined, and will wait for them to reach a "healthy"
#          state before continuing. The script is useful in CI/CD
#          pipelines where it's necessary to wait for
#          services like databases and APIs to be fully ready
#          before running tests or other dependent tasks.
#
# Main Functionality:
#   1. Checks for all Docker services with health checks
#   2. Waits for each service to reach the "healthy" state
#   3. Exits if any service becomes "unhealthy" or times out
#   4. Uses a default waiting timeout (300 seconds) and a default
#      sleep interval between checks (5 seconds), which can be
#      overridden by passing arguments.
#   5. Dumps the container logs on unhealthy status or timeout
#      so startup errors (e.g. WireMock mapping conflicts) are
#      visible in the CI output.
#
# Arguments:
#   - MAX_WAIT_SECONDS: Optional. Maximum wait time for health status
#                        to be achieved (default is 300 seconds).
#   - SLEEP_INTERVAL: Optional. Time between each health check (default is 5 seconds).
#
# Usage:
#   wait-for-healthy.sh [MAX_WAIT_SECONDS] [SLEEP_INTERVAL]
# Example:
#   wait-for-healthy.sh 180 3
#
# ========================================================

DEFAULT_MAX_WAIT_SECONDS=300    # Default timeout: 5 minutes
DEFAULT_SLEEP_INTERVAL=5        # Default sleep interval: 5 seconds

validate_positive_integer() {
    local value="$1"
    local param_name="$2"
    if ! [[ "$value" =~ ^[1-9][0-9]*$ ]] && [ -n "$value" ]; then
        echo "Error: $param_name must be a positive integer"
        exit 1
    fi
}

validate_positive_integer "$1" "MAX_WAIT_SECONDS"
validate_positive_integer "$2" "SLEEP_INTERVAL"

MAX_WAIT_SECONDS="${1:-$DEFAULT_MAX_WAIT_SECONDS}"
SLEEP_INTERVAL="${2:-$DEFAULT_SLEEP_INTERVAL}"

echo "🕰️ Waiting timeout: $MAX_WAIT_SECONDS seconds"
echo "⏳ Sleep interval: $SLEEP_INTERVAL seconds"

START_TIME=$(date +%s)

echo "🔍 Finding services with healthchecks..."
SERVICES=$(docker ps --filter "health=starting" --filter "health=unhealthy" --filter "health=healthy" --format '{{.Names}}')

if [ -z "$SERVICES" ]; then
  echo "⚠️ No services with healthchecks found. Skipping wait."
  exit 0
fi

# ── Helper: dump the last N lines of a container's logs to stdout ──────────────
# Called whenever a container becomes unhealthy or the global timeout is reached,
# so that startup errors (e.g. WireMock mapping conflicts, port clashes) are
# visible directly in the CI log without needing a separate log-dump step.
dump_logs() {
  local container="$1"
  local lines="${2:-100}"
  echo ""
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "📋 Container logs for '$container' (last $lines lines):"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  docker logs --tail "$lines" "$container" 2>&1 || echo "(no logs available)"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo ""
}

wait_for_health() {
  local container="$1"

  echo "⏳ Waiting for '$container' to become healthy..."
  
  while true; do
    local ELAPSED
    ELAPSED=$(($(date +%s) - START_TIME))
  
    if [ "$ELAPSED" -ge "$MAX_WAIT_SECONDS" ]; then
      echo "⏰ Global timeout reached after $ELAPSED seconds!"
      dump_logs "$container"
      exit 1
    fi
    
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "not-found")
    
    if [ "$STATUS" = "healthy" ]; then
      echo "✅ $container is healthy!"
      return 0
    elif [ "$STATUS" = "unhealthy" ]; then
      echo "❌ $container is unhealthy."
      dump_logs "$container"
      exit 1
    elif [ "$STATUS" = "not-found" ]; then
      echo "⚠️ $container not found. Retrying..."
    else
      echo "⌛ $container status: $STATUS. Waiting..."
    fi
    
    sleep "$SLEEP_INTERVAL"
  done
}

for svc in $SERVICES; do
  wait_for_health "$svc" &
done

wait
