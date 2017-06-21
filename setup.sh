#!/bin/bash
[ `whoami` = root ] || exec su -c $0 root
echo -e "\n\n\n###################################### Setup.sh ######################################\n"
echo -e "\n###################################### Install Composer ######################################\n"
apt-get install -y composer
echo -e "\n###################################### Install project dependencies via Composer ######################################\n"
composer install
export LC_ALL=C
echo "APP_KEY=" | dd of=.env
php artisan key:generate
php artisan config:cache
echo -e "\n###################################### Create symlinks ######################################\n"
rm -r /var/www/html
ln -s /var/www/StarAPI/public/ /var/www/html
echo -e "\n###################################### Start Cron Job ######################################\n"
(crontab -l 2>/dev/null; echo "* * * * * php /var/www/StarAPI/artisan schedule:run 1>> /dev/null 2>&1") | crontab -
touch /var/www/StarAPI/storage/logs/laravel.log
chown -R www-data:www-data /var/www/StarAPI/
echo -e "\n\n\n###################################### Setup.sh Complete ######################################\n"
