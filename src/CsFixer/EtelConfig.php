<?php

declare(strict_types=1);

namespace Etel\CodingStandard\CsFixer;

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/**
 * A php-cs-fixer configuration with the `@Etel/standard` rule set and the fixers it needs registered.
 *
 * In a project's `.php-cs-fixer.dist.php`:
 *
 *     return EtelConfig::create(finder: Finder::create()->in(__DIR__)->exclude(['var', 'vendor']));
 *
 * `$rules` are added on top of the set: a rule the project does differently, never a copy of the set.
 */
final class EtelConfig
{
    /**
     * @param array<string, array<string, mixed>|bool> $rules
     */
    public static function create(Finder $finder, array $rules = []): Config
    {
        // positional: php-cs-fixer marks its API @no-named-arguments
        return new Config()
            ->registerCustomFixers([new NoTrailingCommaOutsideArraysFixer()])
            ->registerCustomRuleSets([new EtelRuleSet()])
            ->setUsingCache(true)
            ->setRiskyAllowed(true)
            ->setRules([EtelRuleSet::NAME => true, ...$rules])
            ->setFinder($finder);
    }
}
