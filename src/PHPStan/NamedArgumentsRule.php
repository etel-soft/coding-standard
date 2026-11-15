<?php

declare(strict_types=1);

namespace Etel\CodingStandard\PHPStan;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

use function array_map;
use function getenv;
use function implode;
use function json_encode;

/**
 * Every argument that can be passed by name must be: `count(value: $items)`, `new Foo(bar: 1)`.
 *
 * Left alone, because naming is impossible or would change the call:
 *  - a callee that refuses named arguments (`@no-named-arguments`, e.g. all of PHPUnit);
 *  - a call that reaches a variadic parameter or unpacks (`...$args`): the earlier arguments
 *    would have to stay positional anyway (no positional argument may follow a named one);
 *  - the call a pipe makes (`$value |> trim(...)`): the piped value has no name to take;
 *  - a callee PHPStan cannot pin down (mixed, a plain `callable`), or whose variants name the
 *    parameters differently (a union of unrelated types).
 *
 * ETEL_NAMED_ARGUMENTS_FIX=1 appends the callee and the argument positions for a rewrite script
 * (which checks the names against the runtime: the stubs of built-ins may differ).
 *
 * @implements Rule<CallLike>
 */
final readonly class NamedArgumentsRule implements Rule
{
    /**
     * Set by PHPStan on the call it makes of `$x |> f(...)`: the piped value is positional by nature
     * (an attribute of its parser, not under its BC promise).
     */
    private const string PIPED_CALL = 'virtualPipeOperatorCall';

    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {}

    #[Override]
    public function getNodeType(): string
    {
        return CallLike::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable() || $node->hasAttribute(key: self::PIPED_CALL)) {
            return [];
        }

        $positional = self::positional(call: $node);
        $callee = $positional === [] ? null : $this->callee(call: $node, scope: $scope);

        if ($callee === null || !$callee->acceptsNamedArguments()->yes()) {
            return [];
        }

        $names = $this->parameterNames(callee: $callee, call: $node, scope: $scope);
        $missing = $names === null ? null : self::missing(positional: $positional, names: $names);

        if ($missing === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(message: self::message(callee: $callee, missing: $missing))
                ->identifier(identifier: 'etel.namedArguments')
                ->build(),
        ];
    }

    /**
     * The positional arguments by their index; none when the call unpacks (`...$args`).
     *
     * @return array<Arg>
     */
    private static function positional(CallLike $call): array
    {
        $positional = [];

        foreach ($call->getArgs() as $index => $arg) {
            if ($arg->unpack) {
                return [];
            }

            if ($arg->name === null) {
                $positional[$index] = $arg;
            }
        }

        return $positional;
    }

    /**
     * Each positional argument with its offset in the file, the name it should take and its index;
     * null when one of them goes beyond the declared parameters or into a variadic one (it cannot be named).
     *
     * @param array<Arg>                $positional
     * @param list<array{string, bool}> $names
     *
     * @return null|list<array{int, string, int}>
     */
    private static function missing(array $positional, array $names): ?array
    {
        $missing = [];

        foreach ($positional as $index => $arg) {
            if (!isset($names[$index]) || $names[$index][1]) {
                return null;
            }

            $missing[] = [$arg->getStartFilePos(), $names[$index][0], $index];
        }

        return $missing;
    }

    /**
     * @param list<array{int, string, int}> $missing
     */
    private static function message(ExtendedMethodReflection|FunctionReflection $callee, array $missing): string
    {
        $labels = array_map(
            callback: static fn (array $argument): string => $argument[1] . ':',
            array: $missing
        );
        $message = 'Name the positional arguments: ' . implode(separator: ', ', array: $labels) . '.';

        if (getenv(name: 'ETEL_NAMED_ARGUMENTS_FIX') === '1') {
            $message .= ' @@' . json_encode(
                value: ['callee' => self::calleeName(callee: $callee), 'arguments' => $missing],
                flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            );
        }

        return $message;
    }

    /**
     * The callee's parameters in order, each with whether it is variadic; null when its variants name them differently.
     *
     * @return null|list<array{string, bool}>
     */
    private function parameterNames(
        ExtendedMethodReflection|FunctionReflection $callee,
        CallLike $call,
        Scope $scope
    ): ?array {
        // the named-arguments variants carry the real parameter names (the positional ones of some
        // built-ins are historical: max($arg1, $arg2, ...) is max($value, ...$values) when named)
        $variants = $callee->getNamedArgumentsVariants() ?? $callee->getVariants();
        $acceptor = ParametersAcceptorSelector::selectFromArgs(
            scope: $scope,
            args: $call->getArgs(),
            parametersAcceptors: $variants,
            namedArgumentsVariants: null
        );

        $names = [];

        foreach ($acceptor->getParameters() as $parameter) {
            $names[] = [$parameter->getName(), $parameter->isVariadic()];
        }

        foreach ($variants as $variant) {
            foreach ($variant->getParameters() as $index => $parameter) {
                if (isset($names[$index]) && $names[$index][0] !== $parameter->getName()) {
                    return null;
                }
            }
        }

        return $names;
    }

    private function callee(CallLike $call, Scope $scope): ExtendedMethodReflection|FunctionReflection|null
    {
        return match (true) {
            $call instanceof FuncCall => $this->function(call: $call, scope: $scope),
            $call instanceof MethodCall,
            $call instanceof NullsafeMethodCall => self::method(call: $call, scope: $scope),
            $call instanceof StaticCall => self::staticMethod(call: $call, scope: $scope),
            $call instanceof New_ => $this->constructor(call: $call, scope: $scope),
            default => null
        };
    }

    private function function(FuncCall $call, Scope $scope): ?FunctionReflection
    {
        if (
            !$call->name instanceof Name
            || !$this->reflectionProvider->hasFunction(nameNode: $call->name, namespaceAnswerer: $scope)
        ) {
            return null;
        }

        return $this->reflectionProvider->getFunction(nameNode: $call->name, namespaceAnswerer: $scope);
    }

    private static function method(MethodCall|NullsafeMethodCall $call, Scope $scope): ?ExtendedMethodReflection
    {
        if (!$call->name instanceof Identifier) {
            return null;
        }

        return $scope->getMethodReflection(
            typeWithMethod: $scope->getType(node: $call->var),
            methodName: $call->name->toString()
        );
    }

    private static function staticMethod(StaticCall $call, Scope $scope): ?ExtendedMethodReflection
    {
        if (!$call->class instanceof Name || !$call->name instanceof Identifier) {
            return null;
        }

        return $scope->getMethodReflection(
            typeWithMethod: new ObjectType(className: $scope->resolveName(name: $call->class)),
            methodName: $call->name->toString()
        );
    }

    private function constructor(New_ $call, Scope $scope): ?ExtendedMethodReflection
    {
        if (!$call->class instanceof Name) {
            return null;
        }

        $class = $scope->resolveName(name: $call->class);

        if (!$this->reflectionProvider->hasClass(className: $class)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass(className: $class);

        return $classReflection->hasConstructor() ? $classReflection->getConstructor() : null;
    }

    private static function calleeName(ExtendedMethodReflection|FunctionReflection $callee): string
    {
        return $callee instanceof FunctionReflection
            ? $callee->getName()
            : $callee->getDeclaringClass()->getName() . '::' . $callee->getName();
    }
}
