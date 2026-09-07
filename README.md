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

The check is skipped when there is no `timestamp` at all. That is a property of
the rule, not a compatibility affordance to rely on: every signer in the fleet -
the legacy backend's `App::signQueryString()` and the new backend's port of it -
appends a timestamp to everything it signs, so in practice the parameter is
always there and the check always runs. The conditional shape matters because it
cannot be evaded (the timestamp is inside the string the signature is computed
over), not because some caller is expected to omit it.

### Bumping your pin is a breaking change - set the window first

**The 300-second default will break apps that work today.** Because every signer
emits a timestamp, every signed URL becomes checkable the moment you bump, and
some of them are *meant* to be old: a shop's order page bakes a signed `direct`
app link into its HTML (`get_direct_app_link` in the legacy backend), and the
owner clicks "Get GLS Print Label" whenever they get to it - often far more than
five minutes after the page was drawn. The OAuth install redirect carries a
timestamp too.

So set the window in the provider's factory in the same change that bumps the
pin, not afterwards:

```php
// after $app->register(new AuthorizeServiceProvider(), $parameters);
$app['validator.hmac'] = function ($app) {
    return new HmacValidator($app['api.app_secret'], 86400);
};
```

Pick the window per app: minutes for a machine-to-machine handshake, hours or a
day for anything a person clicks out of a page that was rendered earlier.

Tests
-----
```bash
./bin/test.sh
```

License
-------
MIT
