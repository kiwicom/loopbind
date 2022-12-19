<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Helpers;

use Nette\StaticClass;
use function php_uname;

final class PlatformHelpers
{
    use StaticClass;

    public static function isOSX(): bool
    {
        return php_uname('s') === 'Darwin';
    }

    public static function isLinux(): bool
    {
        return php_uname('s') === 'Linux';
    }
}
