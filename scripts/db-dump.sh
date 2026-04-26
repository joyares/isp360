#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
env_file="$repo_root/.env"
dump_file="${1:-database/snapshots/isp360-data.sql.gz}"

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

hash_file() {
  local file_path="$1"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$file_path" | awk '{print $1}'
  else
    shasum -a 256 "$file_path" | awk '{print $1}'
  fi
}

get_file_size_bytes() {
  local file_path="$1"
  if stat -c%s "$file_path" >/dev/null 2>&1; then
    stat -c%s "$file_path"
  else
    stat -f%z "$file_path"
  fi
}

db_host="$(get_env DB_HOST 127.0.0.1)"
db_port="$(get_env DB_PORT 3306)"
db_name="$(get_env DB_NAME isp360)"
db_user="$(get_env DB_USER root)"
db_pass="$(get_env DB_PASS '')"
mysql_bin_dir="$(get_env MYSQL_BIN_DIR '')"
max_dump_mb="$(get_env DB_DUMP_MAX_MB 50)"

if [[ -n "$mysql_bin_dir" ]]; then
  mysqldump_bin="$mysql_bin_dir/mysqldump"
else
  mysqldump_bin="$(command -v mysqldump || true)"
fi

if [[ -z "$mysqldump_bin" || ! -x "$mysqldump_bin" ]]; then
  echo "mysqldump not found. Set MYSQL_BIN_DIR in .env or install mysql-client." >&2
  exit 1
fi

dump_path="$repo_root/$dump_file"
dump_dir="$(dirname "$dump_path")"
mkdir -p "$dump_dir"

temp_sql_path="$dump_dir/isp360-data.tmp.sql"
existing_sql_path="$dump_dir/isp360-data.current.sql"
legacy_sql_path="$dump_dir/isp360-data.sql"

args=(
  "--host=$db_host"
  "--port=$db_port"
  "--user=$db_user"
  "--single-transaction"
  "--skip-comments"
  "--skip-dump-date"
  "--routines"
  "--triggers"
  "--events"
  "--add-drop-table"
  "$db_name"
)

if [[ -n "$db_pass" ]]; then
  args+=("--password=$db_pass")
fi

"$mysqldump_bin" "${args[@]}" > "$temp_sql_path"

existing_same_content=0
if [[ -f "$dump_path" ]]; then
  rm -f "$existing_sql_path"
  if [[ "$dump_path" == *.gz ]]; then
    gzip -dc "$dump_path" > "$existing_sql_path"
  else
    cp "$dump_path" "$existing_sql_path"
  fi

  if [[ -f "$existing_sql_path" ]]; then
    new_hash="$(hash_file "$temp_sql_path")"
    existing_hash="$(hash_file "$existing_sql_path")"
    if [[ "$new_hash" == "$existing_hash" ]]; then
      existing_same_content=1
    fi
    rm -f "$existing_sql_path"
  fi
fi

if [[ "$existing_same_content" -eq 1 ]]; then
  rm -f "$temp_sql_path"
  echo "Database dump unchanged: $dump_file"
  exit 0
fi

gzip -c "$temp_sql_path" > "$dump_path"
rm -f "$temp_sql_path"

if [[ -f "$legacy_sql_path" ]]; then
  rm -f "$legacy_sql_path"
fi

dump_bytes="$(get_file_size_bytes "$dump_path")"
max_dump_bytes=$((max_dump_mb * 1024 * 1024))
if (( dump_bytes > max_dump_bytes )); then
  dump_mb=$(awk "BEGIN { printf \"%.2f\", $dump_bytes/1024/1024 }")
  echo "Dump size ${dump_mb}MB exceeds limit ${max_dump_mb}MB. Increase DB_DUMP_MAX_MB in .env if expected." >&2
  exit 1
fi

dump_mb=$(awk "BEGIN { printf \"%.2f\", $dump_bytes/1024/1024 }")
echo "Database dump created: $dump_file (${dump_mb}MB)"
