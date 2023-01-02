<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Commands;

use Kiwicom\Loopbind\Config\Config;
use Kiwicom\Loopbind\Config\ConfigLoader;
use Kiwicom\Loopbind\Constants\ExitCodes;
use Kiwicom\Loopbind\Helpers\BindingHelpers;
use Kiwicom\Loopbind\Helpers\ShellHelpers;
use Kiwicom\Loopbind\Trait\ConfigurationPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplyCommand extends Command
{
    use ConfigurationPrinter;

    protected function configure(): void
    {
        $this->setName('apply')
            ->setDescription('Applies localhost configuration from config in current working directory.');
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

        self::printDesiredConfiguration($config, $output);

        $shellCommands = [];
        if (!(BindingHelpers::isLocalInterfaceAliased($config))) {
            $shellCommands[] = ShellHelpers::getCommandLocalhostAlias($config);
        }
        foreach ($config->getHostname() as $hostname) {
            if (!(BindingHelpers::isHostnameBinded($config, $hostname))) {
                $shellCommands[] = ShellHelpers::getCommandUnbindHostname($config, $hostname);
                $shellCommands[] = ShellHelpers::getCommandBindHostname($config, $hostname);
            }
        }

        if (count($shellCommands) === 0) {
            $output->writeln('<options=bold>No changes needed, everything in place.</>');
            return ExitCodes::SUCCESS;
        }
        $oneCommand = ShellHelpers::getCommandRunAsPrivilegedUserWithForcedPasswordPrompt($shellCommands);
        $output->writeln('<options=bold>Changes needed, following command will be performed:</>');
        $output->writeln($oneCommand);
        exec($oneCommand, $rawOutput, $resultCode);
        $output->writeln($rawOutput);
        if ($resultCode !== 0) {
            $output->writeln('<fg=red;options=bold>Apply failed, see log above.</>');
            return ExitCodes::APPLY_FAILED;
        }
        $output->writeln('<fg=green;options=bold>Apply successful.</>');
        return ExitCodes::SUCCESS;
    }

    private function printDesiredConfiguration(Config $config, OutputInterface $output): void
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
