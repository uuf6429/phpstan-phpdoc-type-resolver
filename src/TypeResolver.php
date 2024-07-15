<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type;
use RuntimeException;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports\Resolver;

class TypeResolver
{
    private const BASIC_TYPES = [
        'int',
        'integer',
        'float',
        'decimal',
        'bool',
        'boolean',
        'string',
        'array',
        'object',
        'resource',
        'callable',
        'void',
        'never',
        'list',
        'null',
        'false',
        'true',
    ];

    private const RELATIVE_TYPES = ['self', 'static', '$this'];

    public function __construct(
        private readonly TypeScope $scope,
        private readonly Resolver  $importsResolver = new Resolver(),
    ) {}

    public function resolve(Type\TypeNode $type): Type\TypeNode
    {
        return $this->resolveType($type);
    }

    /**
     * @template T of null|Type\TypeNode|Type\CallableTypeParameterNode|ConstExpr\ConstExprNode|TemplateTagValueNode
     * @param T $orig
     * @return T
     */
    private function resolveType($orig)
    {
        $constExpr = $orig instanceof Type\ConstTypeNode ? $orig->constExpr : null;

        switch (true) {
            case $orig === null:
                return null;

            case $orig instanceof Type\InvalidTypeNode:
                return $orig;

            case $orig instanceof Type\ArrayShapeItemNode:
                return new Type\ArrayShapeItemNode(
                    keyName: $orig->keyName,
                    optional: $orig->optional,
                    valueType: $this->resolveType($orig->valueType),
                );

            case $orig instanceof Type\ArrayShapeNode:
                return new Type\ArrayShapeNode(
                    items: array_map(
                        fn(Type\ArrayShapeItemNode $item): Type\ArrayShapeItemNode => $this->resolveType($item),
                        $orig->items,
                    ),
                    sealed: $orig->sealed,
                    kind: $orig->kind,
                );

            case $orig instanceof Type\ArrayTypeNode:
                return new Type\ArrayTypeNode(
                    type: $this->resolveType($orig->type),
                );

            case $orig instanceof Type\CallableTypeNode:
                return new Type\CallableTypeNode(
                    identifier: $this->resolveType($orig->identifier),
                    parameters: array_map(
                        fn(Type\CallableTypeParameterNode $item): Type\CallableTypeParameterNode => $this->resolveType($item),
                        $orig->parameters,
                    ),
                    returnType: $this->resolveType($orig->returnType),
                    templateTypes: array_map(
                        fn(TemplateTagValueNode $item): TemplateTagValueNode => $this->resolveType($item),
                        $orig->templateTypes,
                    ),
                );

            case $orig instanceof Type\ConditionalTypeForParameterNode:
                return new Type\ConditionalTypeForParameterNode(
                    parameterName: $orig->parameterName,
                    targetType: $this->resolveType($orig->targetType),
                    if: $this->resolveType($orig->if),
                    else: $this->resolveType($orig->else),
                    negated: $orig->negated,
                );

            case $orig instanceof Type\ConditionalTypeNode:
                return new Type\ConditionalTypeNode(
                    subjectType: $this->resolveType($orig->subjectType),
                    targetType: $this->resolveType($orig->targetType),
                    if: $this->resolveType($orig->if),
                    else: $this->resolveType($orig->else),
                    negated: $orig->negated,
                );

            case $orig instanceof Type\ConstTypeNode && $constExpr !== null:
                switch (true) {
                    case $constExpr instanceof ConstExpr\ConstExprArrayItemNode:
                        return new Type\ConstTypeNode(
                            constExpr: new ConstExpr\ConstExprArrayItemNode(
                                key: $this->resolveType($constExpr->key),
                                value: $this->resolveType($constExpr->value),
                            ),
                        );

                    case $constExpr instanceof ConstExpr\ConstExprArrayNode:
                        return new Type\ConstTypeNode(
                            constExpr: new ConstExpr\ConstExprArrayNode(
                                items: array_map(
                                    fn(ConstExpr\ConstExprArrayItemNode $item): ConstExpr\ConstExprArrayItemNode => $this->resolveType($item),
                                    $constExpr->items,
                                ),
                            ),
                        );

                    case $constExpr instanceof ConstExpr\ConstExprFalseNode:
                    case $constExpr instanceof ConstExpr\ConstExprFloatNode:
                    case $constExpr instanceof ConstExpr\ConstExprIntegerNode:
                    case $constExpr instanceof ConstExpr\ConstExprNullNode:
                    case $constExpr instanceof ConstExpr\ConstExprStringNode:
                    case $constExpr instanceof ConstExpr\ConstExprTrueNode:
                        return $orig;

                    case $constExpr instanceof ConstExpr\ConstFetchNode:
                        return new Type\ConstTypeNode(
                            constExpr: new ConstExpr\ConstFetchNode(
                                className: $this->resolveIdentifier($constExpr->className),
                                name: $constExpr->name,
                            ),
                        );
                }
                throw new RuntimeException('Cannot resolve related types, expression is unsupported: ' . get_class($constExpr));

            case $orig instanceof Type\GenericTypeNode:
                return new Type\GenericTypeNode(
                    type: $this->resolveType($orig->type),
                    genericTypes: array_map(
                        fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item),
                        $orig->genericTypes,
                    ),
                    variances: $orig->variances,
                );

            case $orig instanceof Type\IdentifierTypeNode:
                return new Type\IdentifierTypeNode(
                    name:$this->resolveIdentifier($orig->name),
                );

            case $orig instanceof Type\IntersectionTypeNode:
                return new Type\IntersectionTypeNode(
                    types: array_map(
                        fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item),
                        $orig->types,
                    ),
                );

            case $orig instanceof Type\NullableTypeNode:
                return new Type\NullableTypeNode(
                    type:$this->resolveType($orig->type),
                );

            case $orig instanceof Type\ObjectShapeItemNode:
                return new Type\ObjectShapeItemNode(
                    keyName: $orig->keyName,
                    optional: $orig->optional,
                    valueType: $this->resolveType($orig->valueType),
                );

            case $orig instanceof Type\ObjectShapeNode:
                return new Type\ObjectShapeNode(
                    items: array_map(
                        fn(Type\ObjectShapeItemNode $item): Type\ObjectShapeItemNode => $this->resolveType($item),
                        $orig->items,
                    ),
                );

            case $orig instanceof Type\OffsetAccessTypeNode:
                return new Type\OffsetAccessTypeNode(
                    type: $this->resolveType($orig->offset),
                    offset: $this->resolveType($orig->type),
                );

            case $orig instanceof Type\ThisTypeNode:
                return new Type\IdentifierTypeNode(
                    name: $this->resolveRelativeTypes('$this')
                        ?? throw new LogicException('The `$this` type should always be resolved'),
                );

            case $orig instanceof Type\UnionTypeNode:
                return new Type\UnionTypeNode(
                    types: array_map(
                        fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item),
                        $orig->types,
                    ),
                );

            case $orig instanceof Type\CallableTypeParameterNode:
                return new Type\CallableTypeParameterNode(
                    type: $this->resolveType($orig->type),
                    isReference: $orig->isReference,
                    isVariadic: $orig->isVariadic,
                    parameterName: $orig->parameterName,
                    isOptional: $orig->isOptional,
                );

            case $orig instanceof TemplateTagValueNode:
                return new TemplateTagValueNode(
                    name: $orig->name,
                    bound: $this->resolveType($orig->bound),
                    description: $orig->description ?: '',
                    default: $this->resolveType($orig->default),
                );

            default:
                throw new RuntimeException('Cannot resolve related types, type is unsupported: ' . get_class($orig));
        }
    }

    private function resolveIdentifier(string $symbol): string
    {
        return $this->resolveRelativeTypes($symbol)
            ?? $this->resolveBasicType($symbol)
            ?? $this->resolveImportedType($symbol)
            ?? $this->resolveNamespacedType($symbol)
            ?? $symbol;
    }

    private function resolveRelativeTypes(string $symbol): ?string
    {
        if (!in_array($symbol, self::RELATIVE_TYPES)) {
            return null;
        }

        if ($this->scope->class === null) {
            throw new LogicException("Cannot resolve `$symbol`, no class was defined in the current scope");
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
