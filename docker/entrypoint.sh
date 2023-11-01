# Store the path to PHP.
DOCKER_PHP=$(which php)
(crontab -l 2>/dev/null || true; echo "* * * * * $DOCKER_PHP /var/www/artisan schedule:run >> /dev/null 2>&1") | crontab -

#if [ -f artisan ]; then
#    $DOCKER_PHP artisan optimize:clear
#    $DOCKER_PHP artisan optimize
#fi

# Launch supervisord
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf