#!/bin/bash

version=$1

if [ -z $version ]; then
    echo "please specify a version as the first argument"
    exit 255
fi

echo "Starting docker containers for MediaWiki"
docker-compose -f docker-compose-${version}.yml up -d

# for some reason, this must be run by the user
#echo "Loading database"
echo "run the following now:"
echo "docker exec docker-image_database_1 /bin/bash -c /loaddb.sh"

