<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr;
use PHPStan\PhpDocParser\Ast\PhpDoc;
use PHPStan\PhpDocParser\Ast\Type;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use RuntimeException;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports\Resolver;

/**
 * This is the main class that resolves (fully-qualifies) PHPStan PHPDoc type objects. However, in practice you'd
 * probably want to use the {@see Factory} class instead, since it provides useful helper methods.
 */
class TypeResolver
{
    /**
     * @see https://phpstan.org/writing-php-code/phpdoc-types
     */
    private const BASIC_TYPES = [
        'int', 'integer',
        'string',
        'array-key',
        'bool', 'boolean',
        'true',
        'false',
        'null',
        'float', 'double',
        'scalar',
        'array', 'non-empty-array', 'list', 'non-empty-list',
        'iterable',
        'callable', 'pure-callable', 'pure-Closure',
        'resource', 'closed-resource', 'open-resource',
        'object',
        'mixed',
        'positive-int', 'negative-int', 'non-positive-int', 'non-negative-int', 'non-zero-int',
        'class-string', 'callable-string', 'numeric-string', 'non-empty-string', 'non-falsy-string', 'truthy-string', 'literal-string',
        'void', 'never', 'never-return', 'never-returns', 'no-return',
        'int-mask', 'int-mask-of',
    ];

    private const RELATIVE_TYPES = ['self', 'static', '$this', 'parent'];

    private const RANGE_TYPES =  ['int'];

    private const RANGE_UTILITY_TYPES =  ['min', 'max'];

    private const GENERIC_UTILITY_TYPES = ['new'];

    public function __construct(
        private readonly Scope    $scope,
        private readonly Resolver $importsResolver = new Resolver(),
    ) {
        //
    }

    /**
     * @param list<string> $genericTypes
     */
    public function resolve(Type\TypeNode $type, array $genericTypes = []): Type\TypeNode
    {
        return $this->resolveType($type, $genericTypes, Type\TypeNode::class, false);
    }

    /**
     * @template T of mixed
     * @param list<string> $genericTypes
     * @param class-string<T> $asClass
     * @return ($nullable is true ? ?T : T)
     */
    private function resolveType(mixed $orig, array $genericTypes, string $asClass, bool $nullable)
    {
        $constExpr = $orig instanceof Type\ConstTypeNode ? $orig->constExpr : null;
        $isIntRange = $orig instanceof Type\GenericTypeNode && $orig->type instanceof Type\IdentifierTypeNode && in_array($orig->type->name, self::RANGE_TYPES);
        $isGenericUtilityType = $orig instanceof Type\GenericTypeNode && $orig->type instanceof Type\IdentifierTypeNode && in_array($orig->type->name, self::GENERIC_UTILITY_TYPES);
        if ($orig instanceof Type\CallableTypeNode) {
            foreach ($orig->templateTypes as $templateType) {
                $genericTypes[] = $templateType->name;
            }
        }

        $result = match (true) {
            $orig === null
            => null,

            $orig instanceof Type\InvalidTypeNode
            => $orig,

            $orig instanceof Type\ArrayShapeItemNode
            => new Type\ArrayShapeItemNode(
                keyName: $orig->keyName,
                optional: $orig->optional,
                valueType: $this->resolveType($orig->valueType, $genericTypes, Type\TypeNode::class, false),
            ),

            $orig instanceof Type\ArrayShapeNode
            => new Type\ArrayShapeNode(
                items: array_map(
                    fn(Type\ArrayShapeItemNode $item): Type\ArrayShapeItemNode => $this->resolveType($item, $genericTypes, Type\ArrayShapeItemNode::class, false),
                    $orig->items,
                ),
                sealed: $orig->sealed,
                kind: $orig->kind,
            ),

            $orig instanceof Type\ArrayTypeNode
            => new Type\ArrayTypeNode(
                type: $this->resolveType($orig->type, $genericTypes, Type\TypeNode::class, false),
            ),

            $orig instanceof Type\CallableTypeNode
            => new Type\CallableTypeNode(
                identifier: $this->resolveType($orig->identifier, $genericTypes, Type\IdentifierTypeNode::class, false),
                parameters: array_map(
                    fn(Type\CallableTypeParameterNode $item): Type\CallableTypeParameterNode => $this->resolveType($item, $genericTypes, CallableTypeParameterNode::class, false),
                    $orig->parameters,
                ),
                returnType: $this->resolveType($orig->returnType, $genericTypes, Type\TypeNode::class, false),
                templateTypes: array_map(
                    fn(PhpDoc\TemplateTagValueNode $item): PhpDoc\TemplateTagValueNode => $this->resolveType($item, $genericTypes, PhpDoc\TemplateTagValueNode::class, false),
                    $orig->templateTypes,
                ),
            ),

            $orig instanceof Type\ConditionalTypeForParameterNode
            => new Type\ConditionalTypeForParameterNode(
                parameterName: $orig->parameterName,
                targetType: $this->resolveType($orig->targetType, $genericTypes, Type\TypeNode::class, false),
                if: $this->resolveType($orig->if, $genericTypes, Type\TypeNode::class, false),
                else: $this->resolveType($orig->else, $genericTypes, Type\TypeNode::class, false),
                negated: $orig->negated,
            ),

            $orig instanceof Type\ConditionalTypeNode
            => new Type\ConditionalTypeNode(
                subjectType: $this->resolveType($orig->subjectType, $genericTypes, Type\TypeNode::class, false),
                targetType: $this->resolveType($orig->targetType, $genericTypes, Type\TypeNode::class, false),
                if: $this->resolveType($orig->if, $genericTypes, Type\TypeNode::class, false),
                else: $this->resolveType($orig->else, $genericTypes, Type\TypeNode::class, false),
                negated: $orig->negated,
            ),

            $orig instanceof Type\ConstTypeNode
            => match (true) {
                $constExpr === null,
                $constExpr instanceof ConstExpr\ConstExprFalseNode,
                $constExpr instanceof ConstExpr\ConstExprFloatNode,
                $constExpr instanceof ConstExpr\ConstExprIntegerNode,
                $constExpr instanceof ConstExpr\ConstExprNullNode,
                $constExpr instanceof ConstExpr\ConstExprStringNode,
                $constExpr instanceof ConstExpr\ConstExprTrueNode
                => $orig,

                $constExpr instanceof ConstExpr\ConstExprArrayItemNode
                => new Type\ConstTypeNode(
                    constExpr: new ConstExpr\ConstExprArrayItemNode(
                        key: $this->resolveType($constExpr->key, $genericTypes, ConstExpr\ConstExprNode::class, true),
                        value: $this->resolveType($constExpr->value, $genericTypes, ConstExpr\ConstExprNode::class, false),
                    ),
                ),

                $constExpr instanceof ConstExpr\ConstExprArrayNode
                => new Type\ConstTypeNode(
                    constExpr: new ConstExpr\ConstExprArrayNode(
                        items: array_map(
                            fn(ConstExpr\ConstExprArrayItemNode $item): ConstExpr\ConstExprArrayItemNode => $this->resolveType($item, $genericTypes, ConstExpr\ConstExprArrayItemNode::class, false),
                            $constExpr->items,
                        ),
                    ),
                ),

                $constExpr instanceof ConstExpr\ConstFetchNode
                => new Type\ConstTypeNode(
                    constExpr: new ConstExpr\ConstFetchNode(
                        className: $this->resolveIdentifier($constExpr->className, $genericTypes),
                        name: $constExpr->name,
                    ),
                ),

                default
                => throw new RuntimeException('Cannot resolve related types, expression is unsupported: ' . get_class($constExpr)),
            },

            $orig instanceof Type\GenericTypeNode
            => new Type\GenericTypeNode(
                type: $isGenericUtilityType ? $orig->type : $this->resolveType($orig->type, $genericTypes, Type\IdentifierTypeNode::class, false),
                genericTypes: array_map(
                    fn(Type\TypeNode $item): Type\TypeNode => ($isIntRange && $item instanceof Type\IdentifierTypeNode && in_array($item->name, self::RANGE_UTILITY_TYPES))
                        ? $item : $this->resolveType($item, $genericTypes, Type\TypeNode::class, false),
                    $orig->genericTypes,
                ),
                variances: $orig->variances,
            ),

            $orig instanceof Type\IdentifierTypeNode
            => new Type\IdentifierTypeNode(
                name: $this->resolveIdentifier($orig->name, $genericTypes),
            ),

            $orig instanceof Type\IntersectionTypeNode
            => new Type\IntersectionTypeNode(
                types: array_map(
                    fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item, $genericTypes, Type\TypeNode::class, false),
                    $orig->types,
                ),
            ),

            $orig instanceof Type\NullableTypeNode
            => new Type\NullableTypeNode(
                type: $this->resolveType($orig->type, $genericTypes, Type\TypeNode::class, false),
            ),

            $orig instanceof Type\ObjectShapeItemNode
            => new Type\ObjectShapeItemNode(
                keyName: $orig->keyName,
                optional: $orig->optional,
                valueType: $this->resolveType($orig->valueType, $genericTypes, Type\TypeNode::class, false),
            ),

            $orig instanceof Type\ObjectShapeNode
            => new Type\ObjectShapeNode(
                items: array_map(
                    fn(Type\ObjectShapeItemNode $item): Type\ObjectShapeItemNode => $this->resolveType($item, $genericTypes, Type\ObjectShapeItemNode::class, false),
                    $orig->items,
                ),
            ),

            $orig instanceof Type\OffsetAccessTypeNode
            => new Type\OffsetAccessTypeNode(
                type: $this->resolveType($orig->offset, $genericTypes, Type\TypeNode::class, false),
                offset: $this->resolveType($orig->type, $genericTypes, Type\TypeNode::class, false),
            ),

            $orig instanceof Type\ThisTypeNode
            => new Type\IdentifierTypeNode(
                name: $this->resolveRelativeTypes('$this')
                    ?? throw new LogicException('The `$this` type should always be resolved'),
            ),

            $orig instanceof Type\UnionTypeNode
            => new Type\UnionTypeNode(
                types: array_map(
                    fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item, $genericTypes, Type\TypeNode::class, false),
                    $orig->types,
                ),
            ),

            $orig instanceof Type\CallableTypeParameterNode
            => new Type\CallableTypeParameterNode(
                type: $this->resolveType($orig->type, $genericTypes, Type\TypeNode::class, false),
                isReference: $orig->isReference,
                isVariadic: $orig->isVariadic,
                parameterName: $orig->parameterName,
                isOptional: $orig->isOptional,
            ),

            $orig instanceof PhpDoc\TemplateTagValueNode
            => new PhpDoc\TemplateTagValueNode(
                name: $orig->name,
                bound: $this->resolveType($orig->bound, $genericTypes, Type\TypeNode::class, true),
                description: $orig->description ?: '',
                default: $this->resolveType($orig->default, $genericTypes, Type\TypeNode::class, true),
            ),

            default
            => throw new RuntimeException('Cannot resolve related types, type is unsupported: ' . get_debug_type($orig)),
        };

        assert(
            ($nullable && $result === null) || (is_object($result) && is_a($result, $asClass)),
            'Expected a result of ' . ($nullable ? "?$asClass" : $asClass) . ' but got ' . get_debug_type($result) . ' instead',
        );

        return $result;
    }

    /**
     * @param list<string> $genericTypes
     */
    private function resolveIdentifier(string $symbol, array $genericTypes): string
    {
        return $this->resolveVirtualOrGenericTypes($symbol, $genericTypes)
            ?? $this->resolveRelativeTypes($symbol)
            ?? $this->resolveBasicType($symbol)
            ?? $this->resolveImportedType($symbol)
            ?? $this->resolveNamespacedType($symbol)
            ?? $symbol;
    }

    /**
     * @param list<string> $genericTypes
     */
    private function resolveVirtualOrGenericTypes(string $symbol, array $genericTypes): ?string
    {
        return in_array($symbol, $genericTypes) ? $symbol : null;
    }

    private function resolveRelativeTypes(string $symbol): ?string
    {
        if (!in_array($symbol, self::RELATIVE_TYPES)) {
            return null;
        }

        if ($this->scope->class === null) {
            throw new LogicException("Cannot resolve `$symbol`, no class was defined in the current scope");
        }

        if ($symbol === 'parent') {
            $parent = get_parent_class($this->scope->class);
            return $parent
                ?: throw new LogicException("Class/type `{$this->scope->class}` doesn't have a parent");
        }

        return $this->scope->class;
    }

    private function resolveBasicType(string $symbol): ?string
    {
        return in_array($symbol, self::BASIC_TYPES)
            ? $symbol
            : null;
    }

    private function resolveImportedType(string $symbol): ?string
    {
        $alias = $symbol;

        [$top, $rest] = explode('\\', $symbol, 2) + ['', ''];

        if ($top) {
            $alias = strtolower($top);
        }

        $aliases = $this->importsResolver->getImports($this->scope);

        if (!isset($aliases[$alias])) {
            return null;
        }

        if ($aliases[$alias] === $symbol) {
            return $symbol;
        }

        return rtrim("$aliases[$alias]\\$rest", '\\');
    }

    private function resolveNamespacedType(string $symbol): ?string
    {
        $namespace = $this->importsResolver->getNamespace($this->scope);

        return $namespace ? "$namespace\\$symbol" : null;
    }
}
