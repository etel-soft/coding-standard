<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\CsFixer;

use Etel\CodingStandard\CsFixer\EtelConfig;
use Etel\CodingStandard\CsFixer\EtelRuleSet;
use Etel\CodingStandard\CsFixer\NoTrailingCommaOutsideArraysFixer;
use PhpCsFixer\Finder;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\RuleSet\RuleSets;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function array_key_exists;
use function array_map;

/**
 * @internal
 */
#[CoversClass(EtelConfig::class)]
#[CoversClass(EtelRuleSet::class)]
final class EtelConfigTest extends TestCase
{
    #[Test]
    #[TestDox('The config enables the @Etel/standard set and registers what the set needs')]
    public function enablesTheSet(): void
    {
        $config = EtelConfig::create(finder: Finder::create());

        $this->assertSame(['@Etel/standard' => true], $config->getRules());
        $this->assertTrue($config->getRiskyAllowed());
        $this->assertSame(
            [NoTrailingCommaOutsideArraysFixer::NAME],
            array_map(callback: static fn ($fixer): string => $fixer->getName(), array: $config->getCustomFixers())
        );
        $this->assertSame([EtelRuleSet::NAME], array_map(
            callback: static fn ($set): string => $set->getName(),
            array: $config->getCustomRuleSets()
        ));
    }

    #[Test]
    #[TestDox('A project’s own rules go on top of the set')]
    public function addsProjectRules(): void
    {
        $config = EtelConfig::create(finder: Finder::create(), rules: ['strict_comparison' => false]);

        $this->assertSame(['@Etel/standard' => true, 'strict_comparison' => false], $config->getRules());
    }

    #[Test]
    #[TestDox('The set resolves to concrete rules, the custom fixer among them')]
    public function resolves(): void
    {
        if (!array_key_exists(key: EtelRuleSet::NAME, array: RuleSets::getSetDefinitions())) {
            RuleSets::registerCustomRuleSet(new EtelRuleSet());
        }

        $rules = new RuleSet([EtelRuleSet::NAME => true])->getRules();

        $this->assertArrayHasKey(NoTrailingCommaOutsideArraysFixer::NAME, $rules);
        $this->assertSame(['after_heredoc' => true, 'elements' => ['arrays']], $rules['trailing_comma_in_multiline']);
        // a rule set to false is not part of the resolved set at all
        $this->assertArrayNotHasKey('mb_str_functions', $rules);
    }
}
