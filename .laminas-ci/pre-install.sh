#!/bin/bash

WORKING_DIRECTORY=$2
JOB=$3
PHP_VERSION=$(echo "${JOB}" | jq -r '.php')

apt install -y php8.1-ldap || exit 1
