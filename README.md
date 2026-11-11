# etel coding standard

The coding standard of the etel projects as a dev dependency: one place for the php-cs-fixer rule set, the
fixers it needs, the PHPStan rules and the line-length gate. A project lists only what it does differently, so
a change to the standard is a version bump rather than an edit in every repository.

The conventions themselves (what the tools check and what they cannot) are described in the projects'
`code-conventions.md`.

## Installation

```sh
composer require --dev etel/coding-standard
```

## php-cs-fixer

`.php-cs-fixer.dist.php`:

```php
<?php

declare(strict_types=1);

use Etel\CodingStandard\CsFixer\EtelConfig;
use PhpCsFixer\Finder;

return EtelConfig::create(finder: Finder::create()->in(__DIR__)->exclude(['var', 'vendor']));
```

`EtelConfig::create()` enables the `@Etel/standard` rule set and registers the custom fixer it uses. A project
rule goes in the second argument, on top of the set:

```php
return EtelConfig::create(finder: $finder, rules: ['some_rule' => false]);
```

What the set adds to `@PSR12`, `@PhpCsFixer` and the PHP migrations is in `src/CsFixer/EtelRuleSet.php`. One
rule is ours: `Etel/no_trailing_comma_outside_arrays` removes a trailing comma after the last argument, parameter,
closure `use` variable, attribute argument and `match` arm — php-cs-fixer only ever adds those, or removes them on
a single line.

## PHPStan

`phpstan.dist.neon`:

```neon
includes:
    - vendor/etel/coding-standard/extension.neon
    - vendor/phpstan/phpstan-deprecation-rules/rules.neon

parameters:
    level: 10
    etel:
        jsonWrapper: App\Json\Json
        xmlWrapper: App\Xml\Xml
```

With `phpstan/extension-installer` the first include is not needed.

| Parameter (`etel.`)    | Default | Rule                                                                       |
|------------------------|---------|----------------------------------------------------------------------------|
| `namedArguments`       | `true`  | every argument that can be passed by name is                               |
| `stringInterpolation`  | `true`  | concatenation, never interpolation                                         |
| `doubleConfigurator`   | `true`  | test doubles through `etel/phpunit-double`, not `createStub`/`createMock`  |
| `rawJson`              | `true`  | `json_encode`/`json_decode` only inside `jsonWrapper`                      |
| `jsonWrapper`          | `null`  | the class allowed to call them; `null` — none is                           |
| `rawXml`               | `true`  | `simplexml_load_*` only inside `xmlWrapper`                                |
| `xmlWrapper`           | `null`  | the class allowed to call them; `null` — none is                           |

Every rule is on by default; a project that has not got there yet turns one off and turns it back on when the
code complies.

`ETEL_NAMED_ARGUMENTS_FIX=1` makes the named-arguments rule append the callee and the argument positions to each
message (after `@@`, as JSON), for a script that rewrites the calls.

## Line length

```sh
vendor/bin/etel-line-length
```

Run in the package root: no line in `bin/`, `config/`, `migrations/`, `public/`, `src/`, `tests/` or `tools/` is
longer than 120 characters (characters, not bytes), and no docblock is followed by another one. The generated
`config/reference.php` is skipped.
