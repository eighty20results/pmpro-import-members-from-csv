#!/usr/bin/env bash
SLEEP_TIME=15
echo "Importing database for ${PROJECT_NAME} after a ${SLEEP_TIME} second sleep"
sleep "${SLEEP_TIME}";
echo "Import basedir: $(pwd)"
make wp db import ./mariadb-init/${PROJECT_NAME}.sql
