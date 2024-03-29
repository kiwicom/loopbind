<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Constants;

use Nette\StaticClass;

final class ExitCodes
{
    use StaticClass;

    public const SUCCESS = 0;

    public const NOT_READABLE_CONFIG_FILE = 1;

    public const INVALID_CONFIG_FILE = 2;

    public const APPLY_FAILED = 3;

    public const UNAPPLY_FAILED = 4;

    public const CONFIG_ALREADY_EXISTS = 5;

    public const NEW_CONFIG_INVALID = 6;

    public const UNREADABLE_DOT_ENV = 7;

    public const UNSUPPORTED_PLATFORM = 100;
}
