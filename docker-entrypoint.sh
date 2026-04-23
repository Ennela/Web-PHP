#!/bin/bash
set -e

# ── Fix MPM conflict at runtime (avoid Docker layer cache issues) ──
# Remove ALL mpm modules, then enable only prefork
find /etc/apache2/mods-enabled -name 'mpm_*' -exec rm -f {} + 2>/dev/null || true
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# ── Bind to Railway's dynamic PORT ──
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf

echo "✓ Apache configured: MPM=prefork, PORT=${PORT:-80}"

# Start Apache
exec apache2-foreground
