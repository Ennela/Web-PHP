#!/bin/sh
set -eu
umask 077

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
cd "$project_dir"

runtime=${FRANKENPHP_BIN:-"$project_dir/scratch/runtime/frankenphp"}
if [ ! -x "$runtime" ]; then
    if [ -z "${FRANKENPHP_BIN:-}" ] && command -v frankenphp >/dev/null 2>&1; then
        runtime=$(command -v frankenphp)
    else
        printf 'Khong tim thay FrankenPHP. Cai FrankenPHP hoac dat FRANKENPHP_BIN.\n' >&2
        exit 1
    fi
fi

LOCAL_HTTPS_PORT=${LOCAL_HTTPS_PORT:-8443}
LOCAL_HTTP_PORT=${LOCAL_HTTP_PORT:-8080}
for port in "$LOCAL_HTTPS_PORT" "$LOCAL_HTTP_PORT"; do
    case "$port" in
        ''|*[!0-9]*) printf 'Cong phai la so nguyen tu 1024 den 65535.\n' >&2; exit 1 ;;
    esac
    if [ "$port" -lt 1024 ] || [ "$port" -gt 65535 ]; then
        printf 'Cong phai nam trong khoang 1024 den 65535.\n' >&2
        exit 1
    fi
done
if [ "$LOCAL_HTTPS_PORT" = "$LOCAL_HTTP_PORT" ]; then
    printf 'Cong HTTP va HTTPS phai khac nhau.\n' >&2
    exit 1
fi

# Keep the CA/private keys and runtime state inside the ignored scratch directory.
# Caddy is explicitly configured not to modify system/browser trust stores.
XDG_DATA_HOME="$project_dir/scratch/https/data"
XDG_CONFIG_HOME="$project_dir/scratch/https/config"
mkdir -p "$XDG_DATA_HOME" "$XDG_CONFIG_HOME"
APP_ROOT="$project_dir"
APP_ENV=local
APP_REQUIRE_HTTPS=1
APP_URL="https://localhost:$LOCAL_HTTPS_PORT"
TRUSTED_PROXIES=
export XDG_DATA_HOME XDG_CONFIG_HOME APP_ROOT APP_ENV APP_REQUIRE_HTTPS APP_URL TRUSTED_PROXIES LOCAL_HTTPS_PORT LOCAL_HTTP_PORT

ca_file="$XDG_DATA_HOME/caddy/pki/authorities/local/root.crt"
printf '\n  Website HTTPS: %s/\n  Demo HTTP:     http://localhost:%s/\n\n' "$APP_URL" "$LOCAL_HTTP_PORT"
printf 'Chung chi CA local (duoc tao khi khoi dong):\n  %s\n\n' "$ca_file"
printf 'Trinh duyet can tin CA local nay de hien ket noi hop le. Script khong tu cai CA.\n'
printf 'Kiem tra bang curl: curl --cacert "%s" -I "%s/"\n' "$ca_file" "$APP_URL"
printf 'Nhan Control + C de dung may chu.\n\n'

if [ -f "$ca_file" ] && command -v curl >/dev/null 2>&1 \
    && curl --silent --show-error --cacert "$ca_file" --output /dev/null --max-time 2 "$APP_URL/" 2>/dev/null; then
    printf 'Website HTTPS dang chay.\n'
    exit 0
fi

exec "$runtime" run --config "$project_dir/deploy/Caddyfile.local-https"
