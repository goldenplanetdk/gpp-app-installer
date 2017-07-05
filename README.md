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

License
-------
MIT
