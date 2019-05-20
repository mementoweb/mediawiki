#!/bin/bash

echo "Starting docker containers for MediaWiki"
docker-compose up -d

# for some reason, this must be run by the user
#echo "Loading database"
#docker exec docker-image_database_1 /bin/bash -c /loaddb.sh

