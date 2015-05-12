# php-error-log-parser

## How to use in production

Create mysql table:

```
CREATE TABLE `php_error` (
  `id` int(10) unsigned NOT NULL,
  `datetime` datetime NOT NULL,
  `message` varchar(5000) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `datetime` (`datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;```

Copy and adjust `example-mysql-config.json`, update `error_log` value in `php.ini` corresponding to it.
Install cronjob, make sure that all the paths you use are writeable for the cronjob user.

```
* * * * * /usr/bin/flock -xn /var/run/php-error-log-parser.lock -c '/usr/local/bin/php /root/php-error-log-parser/example-mysql-53.php /root/php-error-log-parser/example-mysql-config.json' >> /var/log/php-error-log-parser.log 2>&1
```

## Advanced usage

Here are few ideas:

* separate servers by `hostname` field in `extra_columns`
* separate fpm/apache/cli the same way
* totally custom writer for any storage
* handle catchable errors, save them in json, parse and separate data in different columns in storage