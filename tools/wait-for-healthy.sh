#!/bin/bash
set -e
set -o pipefail  # Ensures that pipeline failures are caught

# Usage:
#   wait-for-healthy.sh [MAX_WAIT_SECONDS] [SLEEP_INTERVAL]
# Example:
#   wait-for-healthy.sh 180 3

DEFAULT_MAX_WAIT_SECONDS=300    # Default timeout: 5 minutes
DEFAULT_SLEEP_INTERVAL=5        # Default sleep interval: 5 seconds

MAX_WAIT_SECONDS="${1:-$DEFAULT_MAX_WAIT_SECONDS}"
SLEEP_INTERVAL="${2:-$DEFAULT_SLEEP_INTERVAL}"

echo "🕰️ Waiting timeout: $MAX_WAIT_SECONDS seconds"
echo "⏳ Sleep interval: $SLEEP_INTERVAL seconds"

START_TIME=$(date +%s)

echo "🔍 Finding services with healthchecks..."
SERVICES=$(docker compose ps --format json | jq -r '.[] | select(.Health!="") | .Name')

if [ -z "$SERVICES" ]; then
  echo "⚠️ No services with healthchecks found. Skipping wait."
  exit 0
fi

wait_for_health() {
  local container="$1"
  echo "⏳ Waiting for '$container' to become healthy..."

  while true; do
    local STATUS
    STATUS=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "not-found")

    if [ "$STATUS" = "healthy" ]; then
      echo "✅ $container is healthy!"
      break
    elif [ "$STATUS" = "unhealthy" ]; then
      echo "❌ $container is unhealthy."
      exit 1
    elif [ "$STATUS" = "not-found" ]; then
      echo "⚠️ $container not found. Retrying..."
    else
      echo "⌛ $container status: $STATUS. Waiting..."
    fi

    local CURRENT_TIME ELAPSED
    CURRENT_TIME=$(date +%s)
    ELAPSED=$((CURRENT_TIME - START_TIME))

    if [ "$ELAPSED" -ge "$MAX_WAIT_SECONDS" ]; then
      echo "⏰ Timeout reached for $container after $ELAPSED seconds!"
      exit 1
    fi

    sleep "$SLEEP_INTERVAL"
  done
}

for svc in $SERVICES; do
  wait_for_health "$svc"
done
