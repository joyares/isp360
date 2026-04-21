#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 3 ]; then
  echo "Usage: ./scripts/server-first-setup.sh <repo_url> <deploy_path> <branch>"
  exit 1
fi

REPO_URL="$1"
DEPLOY_PATH="$2"
BRANCH="$3"

if [ ! -d "$DEPLOY_PATH/.git" ]; then
  git clone --branch "$BRANCH" "$REPO_URL" "$DEPLOY_PATH"
fi

cd "$DEPLOY_PATH"
cp -n .env.example .env
mkdir -p public/assets/uploads

echo "Server setup complete at $DEPLOY_PATH"
echo "Now edit .env with production DB credentials."
