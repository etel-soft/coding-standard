<?php

declare(strict_types=1);

/*
 * The pcov API as PHPStan should see it where the extension is not loaded (scanFiles in phpstan.neon.dist):
 * signatures of pcov 1.0, with the shapes collect() returns. Never loaded at run time.
 */

namespace pcov;

const inclusive = 1;
const exclusive = 2;

function start(): void {}

function stop(): void {}

/**
 * @param list<string> $filter
 *
 * @return array<string, array<int, int>> file => line => hits (-1: executable, not run)
 */
function collect(int $type = inclusive, array $filter = []): array
{
    return [];
}

function clear(bool $files = false): void {}

/**
 * @return list<string>
 */
function waiting(): array
{
    return [];
}

function memory(): int
{
    return 0;
}
