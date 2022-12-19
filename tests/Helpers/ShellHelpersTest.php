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
        self::assertSame("sed -i.bak '/[[:space:]]mailing\.test$/d' /etc/hosts", ShellHelpers::getCommandUnbindHostname($this->getConfig()));
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
        self::assertSame("echo '127.0.0.23\tmailing.test' >> /etc/hosts", ShellHelpers::getCommandBindHostname($this->getConfig()));
    }

    public function testGetCommandLocalhostUnalias(): void
    {
        if (PlatformHelpers::isLinux()) {
            self::assertSame('true', ShellHelpers::getCommandLocalhostUnalias($this->getConfig()));
            return;
        }
        self::assertSame('ifconfig lo0 -alias \'127.0.0.23\'', ShellHelpers::getCommandLocalhostUnalias($this->getConfig()));
    }

    private function getConfig(): Config
    {
        return new Config('127.0.0.23', 'mailing.test');
    }
}
