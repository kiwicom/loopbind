<?php declare(strict_types=1);

namespace Kiwicom\Loopbind\Commands;

use Kiwicom\Loopbind\Config\Config;
use Kiwicom\Loopbind\Config\ConfigLoader;
use Kiwicom\Loopbind\Constants\ExitCodes;
use Kiwicom\Loopbind\Exceptions\InvalidHostnameException;
use Kiwicom\Loopbind\Exceptions\InvalidIPAddressException;
use Kiwicom\Loopbind\Helpers\IPHelpers;
use Kiwicom\Loopbind\Helpers\ShellHelpers;
use Kiwicom\Loopbind\Trait\ConfigurationPrinter;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use function array_key_exists;
use function file_exists;
use function file_put_contents;
use function filter_var;
use function in_array;
use function is_string;
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
            $output->writeln('<error>Configuration already exists in current working directory.</error>');
            return ExitCodes::CONFIG_ALREADY_EXISTS;
        }

        $questionHelper = new QuestionHelper();

        $IPAddress = null;

        $extractedFromEnvVariable = false;
        $valid = false;
        do {
            $populateFromEnvVariable = $questionHelper->ask($input, $output, new Question("<question>ENV variable name to extract IP address from (leave empty if you want to provide manually or want random one):</question>\n"));
            if ($populateFromEnvVariable === null) {
                $valid = true;
            } elseif (!is_string($populateFromEnvVariable)) {
                $output->writeln('<error>Unexpected input. Please try again.</error>');
            } elseif (!ShellHelpers::isValidBashVariable($populateFromEnvVariable)) {
                $output->writeln('<error>Provided string is not a valid BASH variable. Please try again.</error>');
            } else {
                exec(ShellHelpers::getCommandToGetBashVariableValue($populateFromEnvVariable), $rawOutput, $resultCode);
                if ($resultCode !== 0 || !array_key_exists(0, $rawOutput) || filter_var($rawOutput[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                    $output->writeln('<error>The value `' . $rawOutput[0] . '` is not an IPv4 address. Please try again.</error>');
                } else {
                    $output->writeln('<info>Using IP `' . $rawOutput[0] . '` from ENV variable `' . $populateFromEnvVariable . '`.</info>');
                    $IPAddress = $rawOutput[0];
                    $valid = true;
                    $extractedFromEnvVariable = true;
                }
            }
        } while (!$valid);

        $valid = isset($IPAddress);
        while (!$valid) {
            $generateRandom = $questionHelper->ask($input, $output, new ChoiceQuestion("<question>Do you want to generate random free local IP:</question>\n", ['yes', 'no']));
            if ($generateRandom === 'no') {
                $valid = true;
            } elseif ($generateRandom === 'yes') {
                $IPAddress = IPHelpers::findRandomFreeLocalIP();
                $output->writeln('<info>Using IP `' . $IPAddress . '`.</info>');
                $valid = true;
            } else {
                $output->writeln('<error>Unexpected input. Please try again.</error>');
            }
        };

        $valid = isset($IPAddress);
        while (!$valid) {
            $IPAddress = $questionHelper->ask($input, $output, new Question("<question>IPv4 address from local block (127.x.x.x):</question>\n"));
            if (!is_string($IPAddress) || filter_var($IPAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $output->writeln('<error>Invalid IPv4 address. Please try again.</error>');
            } else {
                $valid = true;
            }
        };

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

        if ($extractedFromEnvVariable) {
            return ExitCodes::SUCCESS;
        }
        $valid = false;
        do {
            $setToDotEnv = $questionHelper->ask($input, $output, new ChoiceQuestion("<question>Do you want to create .env file with variable IP:</question>", ['yes', 'no']));
            if ($setToDotEnv === 'no') {
                $valid = true;
            } elseif ($setToDotEnv === 'yes') {
                if (file_exists('.env')) {
                    // check if IP already exists in .env file
                    $envFile = file_get_contents('.env');
                    if ($envFile === false) {
                        $output->writeln('<error>File `.env` already exists but cannot be read.</error>');
                        return ExitCodes::UNREADABLE_DOT_ENV;
                    }
                    if (preg_match('/^IP=(.*)$/m', $envFile, $matches) === 1) {
                        $output->writeln('<error>File `.env` already exists and variable `IP` is set to `' . $matches[1] . '`.</error>');
                        $valid = true;
                    } else {
                        file_put_contents('.env', "\nIP=" . $IPAddress . "\n", FILE_APPEND);
                        $output->writeln('<info>File `.env` already exists but variable `IP` is not set. Variable `IP` was added and set to `' . $IPAddress . ' .</info>');
                        $valid = true;
                    }
                } else {
                    file_put_contents('.env', 'IP=' . $IPAddress);
                    $output->writeln('<info>File `.env` was created with variable `IP` set to `' . $IPAddress . '`.</info>');
                    $valid = true;
                }
            } else {
                $output->writeln('<error>Unexpected input. Please try again.</error>');
            }
        } while (!$valid);

        if (file_exists('.docker-compose.yml')) {
            $output->writeln('<info>You appear to have .docker-compose.yml file in your directory. Run `docker compose stop`, replace port binding from `"80:80"` to `"$IP:80:80"` and then `docker compose up`.</info>');
        }

        return ExitCodes::SUCCESS;
    }
}
