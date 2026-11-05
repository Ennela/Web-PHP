#!/bin/sh
set -eu

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
cd "$project_dir"

site_url=${SITE_ADDRESS:-http://127.0.0.1:8000}
site_url=${site_url%/}

print_links() {
    printf '\n  Website:   %s/\n  Quan tri:  %s/admin/\n\n' "$site_url" "$site_url"
}

# Reuse the running local server instead of starting another on the same port.
if command -v curl >/dev/null 2>&1 && curl --silent --output /dev/null --max-time 2 "$site_url/"; then
    printf 'Website dang chay. Bam Cmd + click vao link de mo.\n'
    print_links
    exit 0
fi

runtime=${FRANKENPHP_BIN:-"$project_dir/scratch/runtime/frankenphp"}
if [ ! -x "$runtime" ]; then
    if [ -z "${FRANKENPHP_BIN:-}" ] && command -v frankenphp >/dev/null 2>&1; then
        runtime=$(command -v frankenphp)
    else
        printf 'Khong tim thay FrankenPHP. Cai FrankenPHP hoac dat FRANKENPHP_BIN.\n' >&2
        exit 1
    fi
fi

printf 'Dang khoi dong du an. Mo link sau khi may chu khoi dong:\n'
print_links
printf 'Nhan Control + C de dung may chu.\n\n'
exec "$runtime" run --config "$project_dir/Caddyfile"
