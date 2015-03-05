# php-error-log-parser

## How to use in production

Create mysql table:

```
CREATE TABLE `php_error` (
  `datetime` datetime DEFAULT NULL,
  `host` varchar(32) NOT NULL,
  `program` varchar(3) NOT NULL,
  `message` varchar(2048) NOT NULL DEFAULT '',
  KEY `php_error_datetime_idx` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Update `error_log` value to `/tmp/php_error_fpm` and `/tmp/php_error_log` in `php.ini`.
Create `/tmp/php_error_cli` and `/tmp/php_error_fpm` dirs. 

Adjust mysql credentials and install this cronjob for `root` user

```
* * * * * /usr/bin/flock -xn /var/run/php-error-log-parser-cli.lock -c 'LOG=/tmp/php_error_cli.log LOG_TEMPORARY_DIR=/tmp/php_error_cli MYSQL_HOST=127.0.0.1 MYSQL_USER=user MYSQL_PASSWORD=password MYSQL_DATABASE=project EXTRA_COLUMNS=host=www1,program=cli /usr/local/bin/php /root/php-error-log-parser/example-mysql-53.php' >> /var/log/php-error-log-parser-cli.log 2>&1

* * * * * /usr/bin/flock -xn /var/run/php-error-log-parser-fpm.lock -c 'LOG=/tmp/php_error_fpm.log LOG_TEMPORARY_DIR=/tmp/php_error_fpm MYSQL_HOST=127.0.0.1 MYSQL_USER=user MYSQL_PASSWORD=password MYSQL_DATABASE=project EXTRA_COLUMNS=host=www1,program=fpm /usr/local/bin/php /root/php-error-log-parser/example-mysql-53.php' >> /var/log/php-error-log-parser-fpm.log 2>&1
```