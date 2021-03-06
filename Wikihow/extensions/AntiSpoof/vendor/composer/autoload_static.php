<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit261f0c61ac5b595492e1ae044536e028
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Wikimedia\\Equivset\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Wikimedia\\Equivset\\' => 
        array (
            0 => __DIR__ . '/..' . '/wikimedia/equivset/src',
        ),
    );

    public static $classMap = array (
        'UtfNormal\\Constants' => __DIR__ . '/..' . '/wikimedia/utfnormal/src/Constants.php',
        'UtfNormal\\Utils' => __DIR__ . '/..' . '/wikimedia/utfnormal/src/Util.php',
        'UtfNormal\\Validator' => __DIR__ . '/..' . '/wikimedia/utfnormal/src/Validator.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit261f0c61ac5b595492e1ae044536e028::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit261f0c61ac5b595492e1ae044536e028::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit261f0c61ac5b595492e1ae044536e028::$classMap;

        }, null, ClassLoader::class);
    }
}
