#!/bin/bash

vendor/bin/satis build satis.json public/

# Set the correct ownership and 
chown -R www-data:www-data /var/www
chmod -R 0775 /var/www

# htaccess not working on TravisCI, so going to try and create manually during install
cp htaccess.txt public/.htaccess
echo 'user:$apr1$q8az96le$e4TdONq75vE6KUlTH6Ggl1' > .htpasswd