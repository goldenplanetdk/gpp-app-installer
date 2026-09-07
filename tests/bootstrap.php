<?php
/**
 * Test bootstrap.
 *
 * This package has no vendor/ of its own - it is always installed as a
 * dependency of an app - so there is no Composer autoloader to require here.
 * The two prefixes below are the whole map: the package is PSR-4 rooted at the
 * repository root (see composer.json), and the tests sit under tests/.
 *
 * The Tests prefix is matched first because it is the longer one; checked the
 * other way round, GoldenPlanet\Gpp\App\Installer\Tests\Validator\Foo would be
 * looked for at Tests/Validator/Foo.php.
 */

spl_autoload_register(function ($class) {
    $map = [
        'GoldenPlanet\\Gpp\\App\\Installer\\Tests\\' => __DIR__ . '/',
        'GoldenPlanet\\Gpp\\App\\Installer\\' => __DIR__ . '/../',
    ];

    foreach ($map as $prefix => $dir) {
        if (strpos($class, $prefix) !== 0) {
            continue;
        }

        $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }

        return;
    }
});
