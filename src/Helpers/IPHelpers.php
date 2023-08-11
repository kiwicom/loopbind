<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Helpers;

use Kiwicom\Loopbind\Exceptions\UnableToFindUnreferencedIPAddressException;
use Nette\StaticClass;
use function file_get_contents;
use function preg_quote;

final class IPHelpers
{
    use StaticClass;

    /**
     * Returns a 127.x.x.x IP address which is not referenced in /etc/hosts
     * @return string
     */
    public static function findRandomFreeLocalIP(): string
    {
        $hosts = file_get_contents('/etc/hosts');
        $range1 = range(0, 255);
        $range2 = range(0, 255);
        $range3 = range(2, 255);
        foreach ($range1 as $i) {
            foreach ($range2 as $j) {
                foreach ($range3 as $k) {
                    $ip = "127.$i.$j.$k";
                    // by regex check if not in /etc/hosts from the start of the line
                    if (preg_match("/^" . preg_quote($ip) . "/m", $hosts) === 1) { // @phpstan-ignore-line
                        return $ip;
                    }
                }
            }
        }
        throw new UnableToFindUnreferencedIPAddressException();
    }
}
