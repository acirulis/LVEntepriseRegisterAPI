#!/bin/bash
# Start and enter running Docker container for Aerones Customer Portal webserver
docker-compose -f ./docker/docker-compose.yml up -d
docker exec -w /var/www/html -it webserver bash

