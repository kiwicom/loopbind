# Loopbind

🛠️️ CLI tool to easily bind localhost network interface with additional IP and ensure matching record in /etc/hosts.

This is a helper tool to be installed via `composer global` to machines where the localhost deployment process of
Docker composition (via `docker-compose.yaml`) is prepared in a way that it binds the ports on IP from localhost subnet block and multiple of such
compositions should be allowed to run in parallel (differentiated by the IP).

Practically speaking this tool ensures that the localhost network interface (`lo0` on OS X) will also have another IP from localhost subnet block
(for example `127.0.0.2`) and there will entry in `/etc/hosts` for particular domain routing to that IP (for example `127.0.0.2 example.test`).

Supported platforms:

| Platform | Support |
|----------|--------|
| OS X     | ✅      |
| Linux    | ✅      |
| Windows  | ❌      |

Required underlying utilities:
* `sudo`
* `ifconfig`
* `sed`

## Installation

Run
```bash
composer global require kiwicom/loopbind
```

Then if you have composer bin directory on the `PATH` you can use it by calling `loopbind` in the CLI.

## Usage

In the project root define a file named `.loopbind.json` with following content:
```json
{
    "localIPAlias": "127.11.23.1",
    "hostname": "foobar.test"
}
```

Then in this directory you can run `loopbind apply` to run commands to ensure the binding.
Also, you can run `loopbind unapply` to remove it.

The commands are idempotent so repeated apply/unapply does nothing (and the apply command does not even need to run the command again).

Please note, that the config is expected to be in the current working directory.

## Development

This projects uses following coding standard:
* PHPCS
* PHPStan analysis (`composer stan`)
* PHPUnit tests. (`composer unit`)

However, due to the expected side effects (changing the computer configuration after run) and application of KISS only a subset
of functionality is tested automatically. Hence, testing of:
- `ApplyCommand`
- `UnapplyCommand`

should be done manually.
