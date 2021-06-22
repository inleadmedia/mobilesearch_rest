MobileSearch RESTful API
=====================

[![GitHub tag](https://img.shields.io/github/tag/filmstriben/mobilesearch_rest.svg?style=flat-square)](https://github.com/filmstriben/mobilesearch_rest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/badges/build.png?b=master)](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/build-status/master)

Documentation
-------------
[Read the Documentation](http://v2.mobilesearch.inlead.ws/web/)

Requirements
------------
1. PHP 7.1.*
2. composer
3. php-mongodb extension
4. mongodb 3.4/3.6 storage

Installation
------------
1. Clone the repository.
2. ``cd PATH_TO_CLONED_REPO``;
2. Run ``composer install``.
3. Run ``php app/console cache:clear --env=prod``.
4. Setup a vhost to point to repository root;
5. Service available @ `http://SERVICE_URL/web/` (this URL should be used as communication endpoint).

Configuration
------------
1. Create and make writebale directory `./web/storage`;
2. Adjust mongodb settings in `app/config.yml`.

First time run
------------
Using a mongodb admin tool (e.g. Rockmongo) or mongo cli create the required database (as set during `composer install`
prompts).
Create `Agency` collection and fill it with required agency credentials, e.g.:
```
{
   "agencyId": "100000",
   "key": "3fa",
   "name": "Dummy",
   "children": []
}
```

###Running tests:
-------------
In terminal, type `./run-tests.sh`.

```shell
./run-tests.sh
PHPUnit 7.5.13 by Sebastian Bergmann and contributors.

.................................                                 33 / 33 (100%)

Time: 3.55 seconds, Memory: 40.00 MB

OK (33 tests, 787 assertions)
```
