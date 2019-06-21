#!/bin/bash

# Store environment variables to be used in cron
printenv | sed 's/^\(.*\)$/export \1/g' > /etc/environment

# launch apache2
service apache2 start

# composer
composer install

# launch cron daemon
cron -f
