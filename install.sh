#!/bin/bash
#
#

[ -f config.js ] || cp -v config.js-dist config.js
[ -f db/sqlite3.db ] || sqlite3 db/sqlite3.db < db/schema.sql

sudo chown -v -R www-data db

echo -e "\n\tplease run $PWD/cronjob/dis-task.php regularly from a cron job!"


cronjob/dis-task.php
