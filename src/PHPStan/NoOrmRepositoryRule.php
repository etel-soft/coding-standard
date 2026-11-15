<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

use function strtolower;

/**
 * No repositories: reads go through queries, and a command handler loads the row it changes with find() by id or
 * changes rows with a DQL statement — never getRepository() on a Doctrine object manager or manager registry.
 * A class that is itself a repository is {@see NoOrmRepositoryClassRule}'s.
 *
 * @implements Rule<MethodCall>
 */
final readonly class NoOrmRepositoryRule implements Rule
{
    private const array MANAGERS = ['Doctrine\Persistence\ObjectManager', 'Doctrine\Persistence\ManagerRegistry'];

    #[Override]
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || strtolower(string: $node->name->toString()) !== 'getrepository') {
            return [];
        }

        $type = $scope->getType(node: $node->var);

        foreach (self::MANAGERS as $manager) {
            if (new ObjectType(className: $manager)->isSuperTypeOf(type: $type)->yes()) {
                return [
                    RuleErrorBuilder::message(
                        message: 'Read through a query, not getRepository(); a command handler loads the row it '
                            . 'changes with find() by id or changes rows with a DQL statement.'
                    )
                        ->identifier(identifier: 'etel.ormRepository')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
