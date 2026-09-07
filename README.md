OBB app Service Provider
---------------------------------------

Install
-------
```bash
composer require "goldenplanetdk/gp-app-installer:dev-master"
```

```php
use GP\App\Provider\Service\AuthorizeServiceProvider;

$app->register(new AuthorizeServiceProvider(), $parameters);
```

Events:

`app.installation.success`
`app.uninstalled`


Create DB table

```sql
CREATE TABLE `installations` (
  id                 INT UNSIGNED AUTO_INCREMENT NOT NULL,
  shop               VARCHAR(256)                NOT NULL
  COLLATE utf8_unicode_ci,
  token              VARCHAR(255)                NOT NULL
  COLLATE utf8_unicode_ci,
  is_secure_protocol TINYINT(1)                  NOT NULL,
  created_at         DATETIME                    NOT NULL,
  PRIMARY KEY (id)
)
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_unicode_ci
  ENGINE = InnoDB;
```

Signature freshness
-------------------

`HmacValidator` verifies the `hmac` over the query string and, **when the query
string carries a `timestamp`**, that it is no more than 300 seconds old in
either direction. Without that window a signature is valid forever, and the
full signed URL - hmac included - sits in the app's web-server access log; the
signature covers no request path, so one old log line is a reusable ticket into
any route of the app that logged it.

The check is skipped when there is no `timestamp` at all, so a caller that has
never sent one keeps working. It cannot be evaded: the timestamp is inside the
string the signature is computed over.

Widen the window for an app whose signed links are rendered into a page and
clicked later - a shop's order page bakes a signed `direct` app link into the
HTML, and the owner may click it long after the page was drawn:

```php
$app['validator.hmac'] = function ($app) {
    return new HmacValidator($app['api.app_secret'], 86400);
};
```

Tests
-----
```bash
./bin/test.sh
```

License
-------
MIT
