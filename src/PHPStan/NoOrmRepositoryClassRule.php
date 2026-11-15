<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * No repositories: a class may not be a Doctrine repository (implement ObjectRepository, as EntityRepository and
 * ServiceEntityRepository do) — reads go through queries. Calls to getRepository() are {@see NoOrmRepositoryRule}'s.
 *
 * @implements Rule<InClassNode>
 */
final readonly class NoOrmRepositoryClassRule implements Rule
{
    private const string REPOSITORY = 'Doctrine\Persistence\ObjectRepository';

    #[Override]
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if ($class->isInterface() || !$class->implementsInterface(className: self::REPOSITORY)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                message: 'Class ' . $class->getName() . ' is a Doctrine repository: read through queries instead.'
            )
                ->identifier(identifier: 'etel.ormRepository')
                ->build(),
        ];
    }
}
