<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Trait;

use Kiwicom\Loopbind\Config\Config;
use Kiwicom\Loopbind\Helpers\BindingHelpers;
use Symfony\Component\Console\Output\OutputInterface;

trait ConfigurationPrinter
{
    private static function printDesiredConfiguration(Config $config, OutputInterface $output): void
    {
        $output->writeln('<options=bold>Loopbind</>');
        $output->writeln('----------------');
        $output->writeln('Desired configuration:');
        $output->writeln("{$config->getLocalAliasIP()} -> 127.0.0.1\t\t" . (BindingHelpers::isLocalInterfaceAliased($config) ? '[<fg=green>READY</>]' : '[<fg=red>MISSING</>]'));
        foreach ($config->getHostname() as $hostname) {
            $output->writeln("{$hostname} -> {$config->getLocalAliasIP()}\t\t" . (BindingHelpers::isHostnameBinded($config, $hostname) ? '[<fg=green>READY</>]' : '[<fg=red>MISSING</>]'));
        }
    }
}
