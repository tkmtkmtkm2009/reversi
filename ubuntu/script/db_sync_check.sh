#!/bin/bash

. db_sync.conf

echo $DB_HOST_TEST
echo $DB_HOST_LOCAL


echo "*** RSYNC TARGET ***"
cat  ./dbCheckList.list
echo

: > temp.sql

cat ./dbCheckList.list | egrep -v "^ *#|^$" | \
while read dbname tablename;do
	echo $dbname $tablename;
	pt-table-sync --print --verbose h=$DB_HOST_TEST,u=$DB_USER_TEST,p=$DB_PASS_TEST,D=$dbname,t=$tablename h=$DB_HOST_LOCAL,u=$DB_USER_LOCAL,p=$DB_PASS_LOCAL,D=$dbname,t=$tablename >> temp.sql
done