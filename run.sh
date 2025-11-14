#!/bin/bash

# Pour avoir la config auto:
# Créer src/phpmyadmi.php
# avec 
# <?php
# $cfg['Servers'][1]['auth_type'] = 'config';
# $cfg['Servers'][1]['host'] = '...';
# $cfg['Servers'][1]['user'] = '...';
# cfg['Servers'][1]['password'] = '...';

docker run --rm -it --name phpmyadmin --net app -p 8080:80 -v ./src/phpmyadmin.php:/etc/phpmyadmin/config.user.inc.php:ro phpmyadmin:latest&

docker run --rm -it --name phpfpm --net app -p 3001:80 -v ./src:/var/www/html:ro -e "WEBROOT=/var/www/html/public/" -e SKIP_COMPOSER=1 -e SKIP_CHMOD=1 -e SKIP_CHOWN=1 -v ./src/nginx.conf:/etc/nginx/sites-enabled/default.conf:ro richarvey/nginx-php-fpm:latest