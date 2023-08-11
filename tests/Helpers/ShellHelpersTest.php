<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Tests\Helpers;

use Kiwicom\Loopbind\Config\Config;
use Kiwicom\Loopbind\Helpers\PlatformHelpers;
use Kiwicom\Loopbind\Helpers\ShellHelpers;
use PHPUnit\Framework\TestCase;

class ShellHelpersTest extends TestCase
{
    public function testGetCommandUnbindHostname(): void
    {
        self::assertSame("sed -i.bak '/[[:space:]]mailing\.test$/d' /etc/hosts", ShellHelpers::getCommandUnbindHostname($this->getConfig(), 'mailing.test'));
    }

    public function testGetCommandLocalhostAlias(): void
    {
        self::assertSame('ifconfig lo0 alias \'127.0.0.23\'', ShellHelpers::getCommandLocalhostAlias($this->getConfig()));
    }

    public function testGetCommandRunAsPrivilegedUserWithForcedPasswordPrompt(): void
    {
        self::assertSame('sudo -k sh -c "a ; b ; c"', ShellHelpers::getCommandRunAsPrivilegedUserWithForcedPasswordPrompt(['a', 'b', 'c']));
    }

    public function testGetCommandDoesLocalhostAliasExist(): void
    {
        if (PlatformHelpers::isLinux()) {
            self::assertSame('true', ShellHelpers::getCommandDoesLocalhostAliasExist($this->getConfig()));
            return;
        }
        self::assertSame('ifconfig lo0 | grep \'127.0.0.23\'', ShellHelpers::getCommandDoesLocalhostAliasExist($this->getConfig()));
    }

    public function testGetCommandBindHostname(): void
    {
        self::assertSame("echo '127.0.0.23\tmailing.test' >> /etc/hosts", ShellHelpers::getCommandBindHostname($this->getConfig(), 'mailing.test'));
    }

    public function testGetCommandLocalhostUnalias(): void
    {
        if (PlatformHelpers::isLinux()) {
            self::assertSame('true', ShellHelpers::getCommandLocalhostUnalias($this->getConfig()));
            return;
        }
        self::assertSame('ifconfig lo0 -alias \'127.0.0.23\'', ShellHelpers::getCommandLocalhostUnalias($this->getConfig()));
    }

    public function testIsValidBashVariable(): void
    {
        self::assertTrue(ShellHelpers::isValidBashVariable('a'));
        self::assertTrue(ShellHelpers::isValidBashVariable('A'));
        self::assertTrue(ShellHelpers::isValidBashVariable('ABC123'));
        self::assertTrue(ShellHelpers::isValidBashVariable('ABC_123'));
        self::assertTrue(ShellHelpers::isValidBashVariable('_A'));
        self::assertTrue(ShellHelpers::isValidBashVariable('B_'));
        self::assertFalse(ShellHelpers::isValidBashVariable('1'));
        self::assertFalse(ShellHelpers::isValidBashVariable('1A'));
        self::assertFalse(ShellHelpers::isValidBashVariable(' '));
        self::assertFalse(ShellHelpers::isValidBashVariable('č'));
    }

    public function testGetCommandToGetBashVariableValue(): void
    {
        self::assertSame('sh -c \'source .env; echo "${A}"\'', ShellHelpers::getCommandToGetBashVariableValue('A'));
        self::assertSame('sh -c \'source .env; echo "${ABC123}"\'', ShellHelpers::getCommandToGetBashVariableValue('ABC123'));
        self::assertSame('sh -c \'source .env; echo "${ABC_123}"\'', ShellHelpers::getCommandToGetBashVariableValue('ABC_123'));
        self::assertSame('sh -c \'source .env; echo "${_A}"\'', ShellHelpers::getCommandToGetBashVariableValue('_A'));
        self::assertSame('sh -c \'source .env; echo "${B_}"\'', ShellHelpers::getCommandToGetBashVariableValue('B_'));
    }

    private function getConfig(): Config
    {
        return new Config('127.0.0.23', 'mailing.test');
    }
}
