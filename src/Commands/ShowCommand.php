<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Commands;

use Kiwicom\Loopbind\Config\ConfigLoader;
use Kiwicom\Loopbind\Constants\ExitCodes;
use Kiwicom\Loopbind\Trait\ConfigurationPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ShowCommand extends Command
{
    use ConfigurationPrinter;

    protected function configure(): void
    {
        $this->setName('show')
            ->setDescription('Show localhost configuration from config in current working directory and its state.');
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

        return ExitCodes::SUCCESS;
    }
}
