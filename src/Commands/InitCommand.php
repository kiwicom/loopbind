<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Commands;

use Kiwicom\Loopbind\Config\Config;
use Kiwicom\Loopbind\Config\ConfigLoader;
use Kiwicom\Loopbind\Constants\ExitCodes;
use Kiwicom\Loopbind\Exceptions\InvalidHostnameException;
use Kiwicom\Loopbind\Exceptions\InvalidIPAddressException;
use Kiwicom\Loopbind\Trait\ConfigurationPrinter;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use function file_put_contents;
use function filter_var;
use function in_array;
use const FILTER_FLAG_HOSTNAME;
use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_IP;

final class InitCommand extends Command
{
    use ConfigurationPrinter;

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Init localhost configuration from config in current working directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configLoader = new ConfigLoader();
        if ($configLoader->exists('.loopbind.json')) {
            return ExitCodes::CONFIG_ALREADY_EXISTS;
        }

        $questionHelper = new QuestionHelper();

        $valid = false;
        do {
            $IPAddress = $questionHelper->ask($input, $output, new Question("<question>IPv4 address from local block:</question>\n"));
            if (!is_string($IPAddress) || filter_var($IPAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $output->writeln('<error>Invalid IPv4 address. Please try again.</error>');
            } else {
                $valid = true;
            }
        } while (!$valid);

        $askNext = true;
        $hostnames = [];
        do {
            $hostname = $questionHelper->ask($input, $output, new Question("<question>Hostname (leave empty to continue):</question>\n"));
            if ($hostname === null) {
                $askNext = false;
                continue;
            }
            if (!is_string($hostname)) {
                continue;
            }
            if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                $output->writeln('<error>Invalid hostname. Please try again.</error>');
            } elseif (in_array($hostname, $hostnames, true)) {
                $output->writeln('<error>This hostname was already provided. Please try again.</error>');
            } else {
                $hostnames[] = $hostname;
            }
        } while (count($hostnames) === 0 || $askNext);

        try {
            /** @var string $IPAddress */
            $config = new Config($IPAddress, $hostnames);
            $encoded = Json::encode($config, Json::PRETTY);
        } catch (InvalidIPAddressException | InvalidHostnameException | JsonException) {
            return ExitCodes::NEW_CONFIG_INVALID;
        }

        file_put_contents('.loopbind.json', $encoded);

        $output->writeln('<fg=green>New config file `.loopbind.json` was created.</>');

        return ExitCodes::SUCCESS;
    }
}
