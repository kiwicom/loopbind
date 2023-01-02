<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Tests\Config;

use Kiwicom\Loopbind\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{
    public function testMissingFile(): void
    {
        $configLoader = new ConfigLoader();
        $this->expectException(\Kiwicom\Loopbind\Exceptions\UnreadableConfigFileException::class);
        $configLoader->load(__DIR__ . '/fixtures/missing.json');
    }

    public function testInvalidFileEmpty(): void
    {
        $configLoader = new ConfigLoader();
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidConfigFileException::class);
        $configLoader->loadAndParse(__DIR__ . '/fixtures/invalid.empty.json');
    }

    public function testInvalidFileEmptyJSON(): void
    {
        $configLoader = new ConfigLoader();
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidConfigFileException::class);
        $configLoader->loadAndParse(__DIR__ . '/fixtures/invalid.emptyjson.json');
    }

    public function testInvalidFileMissingKey1(): void
    {
        $configLoader = new ConfigLoader();
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidConfigFileException::class);
        $configLoader->loadAndParse(__DIR__ . '/fixtures/invalid.missingkey1.json');
    }

    public function testInvalidFileMissingKey2(): void
    {
        $configLoader = new ConfigLoader();
        $this->expectException(\Kiwicom\Loopbind\Exceptions\InvalidConfigFileException::class);
        $configLoader->loadAndParse(__DIR__ . '/fixtures/invalid.missingkey2.json');
    }

    public function testValid(): void
    {
        $configLoader = new ConfigLoader();
        $config = $configLoader->loadAndParse(__DIR__ . '/fixtures/valid.json');
        self::assertSame('127.11.23.1', $config->getLocalAliasIP());
        self::assertSame(['foobar.test'], $config->getHostname());
    }

    public function testValidArray(): void
    {
        $configLoader = new ConfigLoader();
        $config = $configLoader->loadAndParse(__DIR__ . '/fixtures/valid.array.json');
        self::assertSame('127.11.23.1', $config->getLocalAliasIP());
        self::assertCount(2, $config->getHostname());
        self::assertSame('www.foobar.test', $config->getHostname()[0]);
        self::assertSame('foobar.test', $config->getHostname()[1]);
    }
}
