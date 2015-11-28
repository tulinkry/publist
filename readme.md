Publist Web Page
=============

Installing
----------

Clone repository to web server's document path and run composer update
```
cd /var/www/
git clone https://github.com/tulinkry/publist
cd publist
composer update

# log and temp must be writable by the web server
sudo chown -R www-data:www-data {log,temp}
```

Generate database schema
```
cd /var/www/publist
php www/index.php orm:schema-tool:create
```
