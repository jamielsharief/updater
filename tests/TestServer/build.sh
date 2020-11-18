#!/bin/bash

vendor/bin/satis build satis.json public/

# Set the correct ownership and 
chown -R www-data:www-data /var/www
chmod -R 0775 /var/www