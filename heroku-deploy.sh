# git push heroku master
# touch /app/storage/logs/laravel.log
# cp /app/keys/* /app/storage/
chmod -R 777 /app/storage/
touch /app/storage/logs/system.log
chmod -R 755 /app/modules/
php /app/artisan migrate
#php /app/artisan migrate --force
#php /app/artisan package:discover
