#!/bin/sh
set -eu

mkdir -p \
    /var/www/bootstrap/cache \
    /var/www/storage/db \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views

touch /var/www/storage/db/database.sqlite

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
