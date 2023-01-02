<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Tests\Config;

use Kiwicom\Loopbind\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testValid(): void
    {
        $config = new Config('127.0.0.2', 'foo-bar.test');
        self::assertSame('127.0.0.2', $config->getLocalAliasIP());
        self::assertSame(['foo-bar.test'], $config->getHostname());
    }

    public function testValidArray(): void
    {
        $config = new Config('127.0.0.2', ['www.foo-bar.test', 'foo-bar.test']);
        self::assertSame('127.0.0.2', $config->getLocalAliasIP());
        self::assertSame(['www.foo-bar.test', 'foo-bar.test'], $config->getHostname());
    }

    public function testInvalidIP(): void
    {
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidIPAddressException::class);
        new Config('127.0.0.259', 'foo-bar.test');
    }

    public function testInvalidIP2(): void
    {
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidIPAddressException::class);
        new Config('foo-bar.test', 'foo-bar.test');
    }

    public function testInvalidHostname(): void
    {
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidHostnameException::class);
        new Config('127.0.0.12', 'localhost');
    }

    public function testInvalidHostname2(): void
    {
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidHostnameException::class);
        new Config('127.0.0.12', 'č.test');
    }
}
