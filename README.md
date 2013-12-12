Fload
=====

File upload script based on HTTP PUT


Easy to use:
curl -T yourfile http://domain.com

or
cat yourfile | curl -T . http://domain.com

or
echo 'test paste' | curl -T . http://domain.com


Output is metadata, URL, and more stuff in JSON.

Installation
===
1. Run curl -sS https://getcomposer.org/installer | php to fetch composer
2. run php composer.phar install

Requirements
===
* HTTP server (nginx, apache, lighttpd)
* PHP 5.3+
* Mongo php extension (sudo pecl install mongo) (or implement your own dataProvider)