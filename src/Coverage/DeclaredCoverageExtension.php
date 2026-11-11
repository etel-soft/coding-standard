<?php

declare(strict_types=1);

namespace Etel\CodingStandard\Coverage;

use Override;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

use const pcov\inclusive;

use function array_key_exists;
use function array_keys;
use function pcov\clear;
use function pcov\collect;

/**
 * Collects, per test, the pcov hits in what the test declares (see {@see DeclaredCoverage}): pcov is cleared when a
 * test starts preparing (before setUp) and read when it finishes (after tearDown), for the declared files only. Code
 * run in setUpBeforeClass() or by data providers counts for nobody.
 *
 * Registered by bin/etel-coverage, which reads {@see self::$covered} when the run ends.
 */
final class DeclaredCoverageExtension implements Extension
{
    /** @var array<string, array<int, true>> file => covered lines */
    public static array $covered = [];

    #[Override]
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements PreparationStartedSubscriber {
            #[Override]
            public function notify(PreparationStarted $event): void
            {
                clear();
            }
        });

        $facade->registerSubscriber(new class implements FinishedSubscriber {
            #[Override]
            public function notify(Finished $event): void
            {
                $test = $event->test();

                if (!$test instanceof TestMethod) {
                    return;
                }

                /** @var class-string $class */
                $class = $test->className();
                $targets = DeclaredCoverage::of(testClass: $class);

                if ($targets === []) {
                    return;
                }

                foreach (collect(type: inclusive, filter: array_keys(array: $targets)) as $file => $lines) {
                    // null means the whole file: `??` would turn it into "no range"
                    if (!array_key_exists(key: $file, array: $targets)) {
                        continue;
                    }

                    foreach ($lines as $line => $hits) {
                        if ($hits > 0 && DeclaredCoverage::declares(ranges: $targets[$file], line: $line)) {
                            DeclaredCoverageExtension::$covered[$file][$line] = true;
                        }
                    }
                }
            }
        });
    }
}
