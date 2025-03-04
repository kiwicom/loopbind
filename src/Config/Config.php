<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Config;

use JsonSerializable;
use function array_map;
use function filter_var;
use function is_array;
use function is_string;
use const FILTER_FLAG_HOSTNAME;
use const FILTER_VALIDATE_DOMAIN;

final class Config implements JsonSerializable
{
    private string $localAliasIP;

    /** @var string|string[] */
    private string|array $hostname;

    /**
     * @param string $localAliasIP
     * @param string|array<string> $hostname
     */
    public function __construct(
        string $localAliasIP,
        string|array $hostname
    ) {
        if (filter_var($localAliasIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidIPAddressException("Value `{$localAliasIP}` is not valid IPv4 address.");
        }
        if (is_string($hostname)) {
            if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                throw new \Kiwicom\Loopbind\Exceptions\InvalidHostnameException("Value `{$hostname}` is not valid hostname.");
            }
        }
        if (is_array($hostname)) {
            array_map(fn (string $host): bool => filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false ?
                throw new \Kiwicom\Loopbind\Exceptions\InvalidHostnameException("Value `{$host}` is not valid hostname.") : true, $hostname);

            array_map(fn (string $hostname): bool => $hostname === 'localhost' ?
                    throw new \Kiwicom\Loopbind\Exceptions\InvalidHostnameException("Hostname `{$hostname}` is forbidden by this tool.") : true, $hostname);
        }
        if ($hostname === 'localhost') {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidHostnameException("Hostname `{$hostname}` is forbidden by this tool.");
        }

        $this->localAliasIP = $localAliasIP;
        $this->hostname = $hostname;
    }

    public function getLocalAliasIP(): string
    {
        return $this->localAliasIP;
    }

    /**
     * @return array<string>
     */
    public function getHostname(): array
    {
        if (is_string($this->hostname)) {
            return [$this->hostname];
        }
        return $this->hostname;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'localIPAlias' => $this->localAliasIP,
            'hostname' => $this->hostname
        ];
    }
}
