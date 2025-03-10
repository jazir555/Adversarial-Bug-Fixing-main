<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit54a0fed8d70d8ecaa1b757e214014351
{
    public static $files = array (
        '5f0e95b8df5acf4a92c896dc3ac4bb6e' => __DIR__ . '/..' . '/phpmetrics/phpmetrics/src/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PhpParser\\' => 10,
        ),
        'M' => 
        array (
            'Masterminds\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PhpParser\\' => 
        array (
            0 => __DIR__ . '/..' . '/nikic/php-parser/lib/PhpParser',
        ),
        'Masterminds\\' => 
        array (
            0 => __DIR__ . '/..' . '/masterminds/html5/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'H' => 
        array (
            'Hal\\' => 
            array (
                0 => __DIR__ . '/..' . '/phpmetrics/phpmetrics/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'lessc' => __DIR__ . '/..' . '/leafo/lessphp/lessc.inc.php',
        'lessc_formatter_classic' => __DIR__ . '/..' . '/leafo/lessphp/lessc.inc.php',
        'lessc_formatter_compressed' => __DIR__ . '/..' . '/leafo/lessphp/lessc.inc.php',
        'lessc_formatter_lessjs' => __DIR__ . '/..' . '/leafo/lessphp/lessc.inc.php',
        'lessc_parser' => __DIR__ . '/..' . '/leafo/lessphp/lessc.inc.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit54a0fed8d70d8ecaa1b757e214014351::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit54a0fed8d70d8ecaa1b757e214014351::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit54a0fed8d70d8ecaa1b757e214014351::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit54a0fed8d70d8ecaa1b757e214014351::$classMap;

        }, null, ClassLoader::class);
    }
}
