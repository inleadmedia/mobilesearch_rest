MobileSearch RESTful API
=====================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/inleadmedia/mobilesearch_rest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/inleadmedia/mobilesearch_rest/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/inleadmedia/mobilesearch_rest/badges/build.png?b=master)](https://scrutinizer-ci.com/g/inleadmedia/mobilesearch_rest/build-status/master)

Documentation
-------------

[Read the Documentation](http://am.fs_rest.dev.inlead.dk/web/)

Requirements
------------
1. PHP 5.4+
2. composer
3. php5-mongo extension
4. mongodb storage

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
1. Create and make writebale directory `web/files`;
2. Adjust mongodb settings in `app/config.yml`.

First time run
------------
Using a mongodb admin tool (e.g. Rockmongo) or any else create the required database (as in `config.yml`, by default it's `fs`).
Create `Agency` collection and fill it with required agency crendentials, e.g.:
```
{
   "agencyId": "100000",
   "key": "3fa",
   "name": "Dummy",
   "children": []
}	
```

License
-------

This bundle is under the GNU GPL license.
