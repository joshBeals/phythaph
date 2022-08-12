# git push heroku master
# touch /app/storage/logs/laravel.log
# cp /app/keys/* /app/storage/
chmod -R 777 /app/storage/
touch /app/storage/logs/system.log
php /app/artisan migrate
php /app/artisan scribe:generate
#php /app/artisan migrate --force
#php /app/artisan package:discover
