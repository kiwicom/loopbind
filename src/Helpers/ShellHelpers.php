<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Helpers;

use Kiwicom\Loopbind\Config\Config;
use Nette\StaticClass;
use function escapeshellarg;
use function implode;

final class ShellHelpers
{
    use StaticClass;

    /**
     * Returns shell command to add alias on localhost network interface for local IP according to config
     *
     * @param Config $config
     *
     * @return string
     */
    public static function getCommandLocalhostAlias(Config $config): string
    {
        return 'ifconfig lo0 alias ' . escapeshellarg($config->getLocalAliasIP());
    }

    /**
     * Returns shell command to remove alias on localhost network interface for local IP according to config
     *
     * @param Config $config
     *
     * @return string
     */
    public static function getCommandLocalhostUnalias(Config $config): string
    {
        if (PlatformHelpers::isLinux()) {
            return 'true';
        }
        return 'ifconfig lo0 -alias ' . escapeshellarg($config->getLocalAliasIP());
    }

    /**
     * Returns shell command to check whether alias on localhost network interface for local IP according to config exists
     *
     * @param Config $config
     *
     * @return string
     */
    public static function getCommandDoesLocalhostAliasExist(Config $config): string
    {
        if (PlatformHelpers::isLinux()) {
            return 'true';
        }
        return 'ifconfig lo0 | grep ' . escapeshellarg($config->getLocalAliasIP());
    }

    /**
     * Returns shell command to remove previous entry of hostname according to config from /etc/hosts file
     *
     * @param Config $config
     * @param string $hostname
     *
     * @return string
     */
    public static function getCommandUnbindHostname(Config $config, string $hostname): string
    {
        return "sed -i.bak '/[[:space:]]" . self::escapeHostnameSedPattern($hostname) . "$/d' /etc/hosts";
    }

    /**
     * Returns shell command to add entry of hostname according to config to /etc/hosts file
     *
     * @param Config $config
     * @param string $hostname
     *
     * @return string
     */
    public static function getCommandBindHostname(Config $config, string $hostname): string
    {
        return 'echo \'' . $config->getLocalAliasIP() . "\t" . $hostname . '\' >> /etc/hosts';
    }

    /**
     * Returns shell command which runs given shell commands as privileged user via SUDO utility with forcing password prompt at all attempts
     *
     * @param string[] $commands
     *
     * @return string
     */
    public static function getCommandRunAsPrivilegedUserWithForcedPasswordPrompt(array $commands): string
    {
        return 'sudo -k sh -c "' . implode(' ; ', $commands) . '"';
    }

    /**
     * Returns whether the given string is valid bash variable name
     * @param string $variableName
     * @return bool
     */
    public static function isValidBashVariable(string $variableName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $variableName) === 1;
    }

    public static function getCommandToGetBashVariableValue(string $variableName): string
    {
        // extract value from current ENV variable with .env applied with using bash command
        return 'sh -c \'source .env; echo "${' . $variableName . '}"\'';
    }

    /**
     * Return escaped hostname to be used as sed pattern
     *
     * @param string $pattern
     *
     * @return string
     */
    private static function escapeHostnameSedPattern(string $pattern): string
    {
        return str_replace(['.'], ['\.'], $pattern);
    }
}
