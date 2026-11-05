#!/bin/sh
set -eu

# Validate the dynamic Railway port before writing it into Apache configuration.
app_port=${PORT:-80}
case "$app_port" in
    ''|*[!0-9]*) echo 'PORT must be an integer between 1 and 65535.' >&2; exit 1 ;;
esac
if [ "$app_port" -lt 1 ] || [ "$app_port" -gt 65535 ]; then
    echo 'PORT must be an integer between 1 and 65535.' >&2
    exit 1
fi

# The official Apache PHP image uses prefork; make startup deterministic.
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null
printf 'Listen %s\n' "$app_port" > /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9][0-9]*>/<VirtualHost *:${app_port}>/" /etc/apache2/sites-available/000-default.conf

# Fail before accepting traffic when Apache configuration is invalid.
apache2ctl configtest
exec apache2-foreground
