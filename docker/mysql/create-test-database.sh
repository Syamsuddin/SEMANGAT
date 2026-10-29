#!/usr/bin/env bash
# DB tes terpisah (docs/10, docs/13): semangat_test.
mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS semangat_test;
    GRANT ALL PRIVILEGES ON \`semangat_test%\`.* TO '$MYSQL_USER'@'%';
EOSQL
