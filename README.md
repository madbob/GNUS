## GNUS

GNUS is a simple GNU Social accounts generator: each managed account acts as a
proxy for a given Atom/RSS feed, and is constantly updated with incoming news.

# INSTALL

```
git clone
cp conf.php.sample conf.php
[edit conf.php accordly to your needs]
composer install
chown -R www-data:www-data data
```

The `configurations/` folder contains sample configuration files for both Apache
and Nginx.

Data are stored in a SQLite3 database stored by default in `data/data.sqlite3`,
it is automatically generated when not found.

In cron, setup a periodic (hourly?) invokation of the `fetcher.php` script, such
as:

```
0 * * * *	cd /var/www/gnus; php fetcher.php
```

