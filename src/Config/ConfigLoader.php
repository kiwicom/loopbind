<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Config;

use Kiwicom\Loopbind\Exceptions\InvalidConfigFileException;
use Kiwicom\Loopbind\Exceptions\UnreadableConfigFileException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Utils\Json;
use function file_exists;
use function file_get_contents;
use function is_readable;

final class ConfigLoader
{
    /**
     * @param string $filePath
     *
     * @return Config
     *
     * @throws UnreadableConfigFileException
     * @throws InvalidConfigFileException
     */
    public function loadAndParse(string $filePath): Config
    {
        return $this->parse($this->load($filePath), $filePath);
    }

    /**
     * @param string $filePath
     *
     * @return string
     *
     * @throws UnreadableConfigFileException
     */
    public function load(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Kiwicom\Loopbind\Exceptions\UnreadableConfigFileException("File `${filePath}` is not readable or does not exist.");
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \Kiwicom\Loopbind\Exceptions\UnreadableConfigFileException("File `${filePath}` is not readable or does not exist.");
        }
        return $content;
    }

    /**
     * @param string $content
     * @param string $filePath
     *
     * @return Config
     *
     * @throws InvalidConfigFileException
     */
    public function parse(string $content, string $filePath): Config
    {
        try {
            $data = Json::decode($content, Json::FORCE_ARRAY);
        } catch (\Nette\Utils\JsonException $exception) {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidConfigFileException("File `${filePath}` is not a valid JSON file: {$exception->getMessage()}");
        }

        $schema = Expect::structure([
            'localIPAlias' => Expect::string()->required(),
            'hostname' => Expect::string()->required(),
        ])->castTo('array');

        $processor = new Processor();
        try {
            /** @var array{localIPAlias: string, hostname: string} $normalized */
            $normalized = $processor->process($schema, $data);
        } catch (\Nette\Schema\ValidationException $exception) {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidConfigFileException("File `${filePath}` does not contain valid configuration: {$exception->getMessage()}");
        }

        try {
            $config = new Config($normalized['localIPAlias'], $normalized['hostname']);
        } catch (\Kiwicom\Loopbind\Exceptions\InvalidIPAddressException|\Kiwicom\Loopbind\Exceptions\InvalidHostnameException $exception) {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidConfigFileException("File `${filePath}` does not contain valid configuration: {$exception->getMessage()}");
        }
        return $config;
    }
}
