# php-error-log-parser

## How to use

Create mysql table:

```
CREATE TABLE `php_error` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `message` varchar(5000) NOT NULL DEFAULT '',
  `host` varchar(50) NOT NULL DEFAULT '',
  `env` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `datetime` (`datetime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```

Copy and adjust `example-mysql-config.json`, update `error_log` value in `php.ini` corresponding to it.

You can use `flock` and cronjob to deliver auto-recovery in case if any error.


```
* * * * * /usr/bin/flock -xn /var/run/php-error-log-parser.lock -c '/usr/local/bin/php /root/php-error-log-parser/example-mysql-53.php /root/php-error-log-parser-config.json' >> /var/log/php-error-log-parser.log 2>&1
```

## Advanced usage

Here are few ideas:

* separate servers by `hostname` field in `extra_columns`
* separate fpm/apache/cli the same way
* totally custom writer for any storage
* handle all catchable errors, save them in json, parse it in processor, separate data by columns in storage