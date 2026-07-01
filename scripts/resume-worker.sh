#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "Stopping queue workers..."
pkill -f "artisan queue:work" 2>/dev/null || true
pkill -f "artisan queue:listen" 2>/dev/null || true

echo "Starting queue worker..."
php artisan queue:work
