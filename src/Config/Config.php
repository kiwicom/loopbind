<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Config;

use const FILTER_FLAG_HOSTNAME;
use const FILTER_VALIDATE_DOMAIN;

final class Config
{
    private string $localAliasIP;

    private string $hostname;

    public function __construct(
        string $localAliasIP,
        string $hostname
    ) {
        if (filter_var($localAliasIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidIPAddressException("Value `${localAliasIP}` is not valid IPv4 address.");
        }
        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidHostnameException("Value `${hostname}` is not valid hostname.");
        }
        if ($hostname === 'localhost') {
            throw new \Kiwicom\Loopbind\Exceptions\InvalidHostnameException("Hostname `${hostname}` is forbidden by this tool.");
        }

        $this->localAliasIP = $localAliasIP;
        $this->hostname = $hostname;
    }

    public function getLocalAliasIP(): string
    {
        return $this->localAliasIP;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }
}
