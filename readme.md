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

Create local configuration
```
cd /var/www/publist/app/config
nano config.local.neon
```
Modify based on your configuration

```txt
common:
	parameters:
		database:
			driver: pdo_mysql
			dsn: 'pdo_mysql:host=127.0.0.1;dbname=publist'
			host: localhost
			user: root
			password:
			dbname: publist
			charset: utf8
			collation: utf8_czech_ci
			options:
				lazy: yes

		email_user: 'your email user'
		email_password: 'your email password'
		email_server: 'your email server'
		email_port: 143
		email_arguments: novalidate-cert

	database:
		dsn: %database.dsn%
		user: %database.user%
		password: %database.password%
		options: %database.options%

	doctrine:
		user: %database.user%
		password: %database.password%
		dbname: %database.dbname%
		host: %database.host%
		charset: %database.charset%
		driver: %database.driver%
		proxyDir: %appDir%/proxies
		autoGenerateProxyClasses: false
		types: [ Tulinkry\Model\Doctrine\Types\DateTimeType ]
		dql: # install doctrine extensions
			numeric:
				acos: DoctrineExtensions\Query\Mysql\Acos
				asin: DoctrineExtensions\Query\Mysql\Asin
				atan2: DoctrineExtensions\Query\Mysql\Atan2
				atan: DoctrineExtensions\Query\Mysql\Atan
				cos: DoctrineExtensions\Query\Mysql\Cos
				cot: DoctrineExtensions\Query\Mysql\Cot
				hour: DoctrineExtensions\Query\Mysql\Hour
				pi: DoctrineExtensions\Query\Mysql\Pi
				power: DoctrineExtensions\Query\Mysql\Power
				quarter: DoctrineExtensions\Query\Mysql\Quarter
				rand: DoctrineExtensions\Query\Mysql\Rand
				round: DoctrineExtensions\Query\Mysql\Round
				sin: DoctrineExtensions\Query\Mysql\Sin
				std: DoctrineExtensions\Query\Mysql\Std
				tan: DoctrineExtensions\Query\Mysql\Tan
development < common:

console < common:

production < common:
	parameters:
		database:
			driver: pdo_mysql
			dsn: ---
			host: ---
			user: ---
			password: ---
			dbname: ---
			charset: utf8
			collation: utf8_czech_ci
			options:
				lazy: yes

```