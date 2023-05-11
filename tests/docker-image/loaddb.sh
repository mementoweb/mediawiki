# this is to work around Docker's inability to deal with extra commands

cat /backup.sql | /usr/bin/mysql -u root --password=testroot
