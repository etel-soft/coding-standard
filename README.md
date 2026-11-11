# etel coding standard

The coding standard of the etel projects as a dev dependency: one place for the php-cs-fixer rule set, the
fixers it needs, the PHPStan rules, the line-length gate and the coverage gate. A project lists only what it does differently, so
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
vendor/bin/etel-line-length                       # bin, config, migrations, public, src, tests, tools
vendor/bin/etel-line-length src tests other-dir   # only these
```

Run in the package root: no line is longer than 120 characters (characters, not bytes), and no docblock is
followed by another one. Without arguments the directories above are checked (those missing are skipped); named
directories replace them, and each must exist — a typo fails (exit code 2) instead of passing as "nothing too
long". PHP files are checked, and every script in `bin/` whatever its extension. The generated
`config/reference.php` is skipped.

## Coverage

```sh
php -d pcov.enabled=1 vendor/bin/etel-coverage
```

Runs the unit tests (`tests/Unit`) with [pcov](https://github.com/krakjoe/pcov) collecting and fails unless every
executable line of `src/` is covered by a test that **declares** it — PHPUnit's own semantics of
`#[CoversClass]` (the class and the traits it uses), `#[CoversTrait]`, `#[CoversMethod]`, `#[CoversFunction]` and
`#[CoversNothing]`. A line a test merely passes through (a value object the tested class uses) stays uncovered
until its own test exercises it. Other `Covers*` attributes (`CoversNamespace`, `CoversClassesThatExtendClass`, …)
stop the run: they would silently count wrong. Code run in `setUpBeforeClass()` or by data providers counts for
nobody.

Needs `phpunit/phpunit` ^13 and `ext-pcov` (both suggested by the package). It drives pcov itself because with
PHPUnit 13 and pcov 1.0.x `phpunit --coverage-*` reports 0 %. pcov instruments only `pcov.directory` (by default
`src/`, `lib/` or `app/` of the working directory): sources elsewhere need `-d pcov.directory=.`.

| Environment         | Default      | Meaning                                                               |
|---------------------|--------------|-----------------------------------------------------------------------|
| `COVERAGE_SOURCES`  | `src`        | the directories measured, comma-separated                             |
| `COVERAGE_TESTS`    | `tests/Unit` | the test paths run, comma-separated                                   |
| `COVERAGE_SCOPE`    | —            | report only sources under these prefixes, while working on one area   |

Exit code 0 — every line covered and the tests passed; 1 — uncovered lines (listed file by file) or failing tests;
2 — no pcov, or a source directory that does not exist.
