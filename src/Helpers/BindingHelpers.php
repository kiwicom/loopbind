<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Helpers;

use Kiwicom\Loopbind\Config\Config;
use Nette\StaticClass;

final class BindingHelpers
{
    use StaticClass;

    /**
     * Returns true iff hostname resolves to desired IP according to config
     *
     * @param Config $config
     *
     * @return bool
     */
    public static function isHostnameBinded(Config $config): bool
    {
        return gethostbyname($config->getHostname()) === $config->getLocalAliasIP();
    }

    /**
     * Resolves true iff the localhost network interface has IP alias according to config
     *
     * @param Config $config
     *
     * @return bool
     */
    public static function isLocalInterfaceAliased(Config $config): bool
    {
        exec(ShellHelpers::getCommandDoesLocalhostAliasExist($config), $output, $returnCode);
        return $returnCode === 0;
    }
}
