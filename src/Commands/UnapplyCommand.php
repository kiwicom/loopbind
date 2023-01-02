<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Commands;

use Kiwicom\Loopbind\Config\Config;
use Kiwicom\Loopbind\Config\ConfigLoader;
use Kiwicom\Loopbind\Constants\ExitCodes;
use Kiwicom\Loopbind\Helpers\BindingHelpers;
use Kiwicom\Loopbind\Helpers\PlatformHelpers;
use Kiwicom\Loopbind\Helpers\ShellHelpers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UnapplyCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('unapply')
            ->setDescription('Unapplies localhost configuration from config in current working directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configLoader = new ConfigLoader();
        try {
            $config = $configLoader->loadAndParse('.loopbind.json');
        } catch (\Kiwicom\Loopbind\Exceptions\InvalidConfigFileException $e) {
            return ExitCodes::INVALID_CONFIG_FILE;
        } catch (\Kiwicom\Loopbind\Exceptions\UnreadableConfigFileException $e) {
            return ExitCodes::NOT_READABLE_CONFIG_FILE;
        }

        $this->printCurrentConfiguration($config, $output);

        $shellCommands = [];
        if (PlatformHelpers::isOSX() && (BindingHelpers::isLocalInterfaceAliased($config))) {
            $shellCommands[] = ShellHelpers::getCommandLocalhostUnalias($config);
        }
        foreach ($config->getHostname() as $hostname) {
            if ((BindingHelpers::isHostnameBinded($config, $hostname))) {
                $shellCommands[] = ShellHelpers::getCommandUnbindHostname($config, $hostname);
            }
        }


        if (count($shellCommands) === 0) {
            $output->writeln('<options=bold>No changes needed, nothing is applied.</>');
            return ExitCodes::SUCCESS;
        }
        $oneCommand = ShellHelpers::getCommandRunAsPrivilegedUserWithForcedPasswordPrompt($shellCommands);
        $output->writeln('<options=bold>Changes needed, following command will be performed:</>');
        $output->writeln($oneCommand);
        exec($oneCommand, $rawOutput, $resultCode);
        $output->writeln($rawOutput);
        if ($resultCode !== 0) {
            $output->writeln('<fg=red;options=bold>Unapply failed, see log above.</>');
            return ExitCodes::UNAPPLY_FAILED;
        }
        $output->writeln('<fg=green;options=bold>Unapply successful.</>');
        return ExitCodes::SUCCESS;
    }

    private function printCurrentConfiguration(Config $config, OutputInterface $output): void
    {
        $output->writeln('<options=bold>Loopbind</>');
        $output->writeln('----------------');
        $output->writeln('Current configuration:');
        if (PlatformHelpers::isOSX()) {
            $output->writeln("{$config->getLocalAliasIP()} -> 127.0.0.1\t\t" . (BindingHelpers::isLocalInterfaceAliased($config) ? '[<fg=red>TO BE REMOVED</>]' : '[<fg=green>NOT PRESENT</>]'));
        }
        if (PlatformHelpers::isLinux()) {
            $output->writeln("{$config->getLocalAliasIP()} -> 127.0.0.1\t\t" . '[<fg=blue>IRRELEVANT</>]');
        }
        foreach ($config->getHostname() as $hostname) {
            $output->writeln("{$hostname} -> {$config->getLocalAliasIP()}\t\t" . (BindingHelpers::isHostnameBinded($config, $hostname) ? '[<fg=red>TO BE REMOVED</>]' : '[<fg=green>NOT PRESENT</>]'));
        }
    }
}
