#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
env_file="$repo_root/.env"
dump_file="${1:-database/snapshots/isp360-data.sql.gz}"
legacy_dump_file="database/snapshots/isp360-data.sql"

get_env() {
  local key="$1"
  local default_value="$2"
  local value=""

  if [[ -f "$env_file" ]]; then
    value="$(grep -E "^[[:space:]]*${key}=" "$env_file" | tail -n1 | cut -d'=' -f2- || true)"
    value="${value#\"}"
    value="${value%\"}"
    value="${value#\'}"
    value="${value%\'}"
    value="$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  fi

  if [[ -z "$value" ]]; then
    value="${!key:-$default_value}"
  fi

  printf '%s' "$value"
}

db_host="$(get_env DB_HOST 127.0.0.1)"
db_port="$(get_env DB_PORT 3306)"
db_name="$(get_env DB_NAME isp360)"
db_user="$(get_env DB_USER root)"
db_pass="$(get_env DB_PASS '')"
mysql_bin_dir="$(get_env MYSQL_BIN_DIR '')"

if [[ -n "$mysql_bin_dir" ]]; then
  mysql_bin="$mysql_bin_dir/mysql"
else
  mysql_bin="$(command -v mysql || true)"
fi

if [[ -z "$mysql_bin" || ! -x "$mysql_bin" ]]; then
  echo "mysql client not found. Set MYSQL_BIN_DIR in .env or install mysql-client." >&2
  exit 1
fi

dump_path="$repo_root/$dump_file"
legacy_dump_path="$repo_root/$legacy_dump_file"
if [[ ! -f "$dump_path" && -f "$legacy_dump_path" ]]; then
  dump_path="$legacy_dump_path"
fi

if [[ ! -f "$dump_path" ]]; then
  echo "Dump file not found: $dump_file or $legacy_dump_file. Skipping import."
  exit 0
fi

args=(
  "--host=$db_host"
  "--port=$db_port"
  "--user=$db_user"
)

if [[ -n "$db_pass" ]]; then
  args+=("--password=$db_pass")
fi

"$mysql_bin" "${args[@]}" -e "DROP DATABASE IF EXISTS \\`$db_name\\`; CREATE DATABASE \\`$db_name\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [[ "$dump_path" == *.gz ]]; then
  gzip -dc "$dump_path" | "$mysql_bin" "${args[@]}"
else
  "$mysql_bin" "${args[@]}" < "$dump_path"
fi

echo "Database restored from: $dump_path"
