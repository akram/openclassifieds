#!/bin/sh
docker build  -t jetski/open-classifieds .
docker ps -aq |xargs docker rm -f
docker run --name=oc-mysql -e MYSQL_DATABASE=openclassifieds -e MYSQL_ROOT_PASSWORD=secret -d mysql 
docker run --name=oc    -p 80:80 -e OC_SITE_NAME=ACES\ Annonces -e OC_ADMIN_EMAIL=akram@free.fr -e OC_ADMIN_PWD=secret --link oc-mysql:mysql -d jetski/open-classifieds
