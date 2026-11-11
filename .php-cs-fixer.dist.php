<?php

declare(strict_types=1);

use Etel\CodingStandard\CsFixer\EtelConfig;
use PhpCsFixer\Finder;

// The package is held to the standard it ships.
return EtelConfig::create(
    finder: Finder::create()
        ->in([__DIR__ . '/src', __DIR__ . '/tests'])
        ->append([__DIR__ . '/bin/etel-line-length', __DIR__ . '/.php-cs-fixer.dist.php'])
);
