#!/bin/bash

podman run --rm -it --name phpfpm --net app -p 3001:80 -v ./src:/var/www/html -e "WEBROOT=/var/www/html/public/" richarvey/nginx-php-fpm:latest