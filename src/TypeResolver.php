<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

use PHPStan\PhpDocParser\Ast\ConstExpr;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type;
use RuntimeException;

class TypeResolver
{
    private const BASIC_TYPES = ['int', 'float', 'bool', 'string', 'array', 'object', 'resource', 'callable', 'void', 'never', 'list', 'null', 'false', 'true'];
    private const RELATIVE_TYPES = ['self', 'static', '$this'];

    /**
     * @param null|string $scopeFile The file containing the source where the types have occurred.
     * @param null|class-string $scopeClass The fully-qualified class where the types are being resolved for.
     */
    public function __construct(
        private readonly ?string $scopeFile,
        private readonly ?string $scopeClass,
        private readonly PhpImportsResolver $importsResolver = new PhpImportsResolver(),
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

        /**
         * @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection PHPUnit is not able to handle coverage for match expressions correctly.
         */
        switch (true) {
            case $orig === null:
                return null;

            case $orig instanceof Type\InvalidTypeNode:
                return $orig;

            case $orig instanceof Type\ArrayShapeItemNode:
                return new Type\ArrayShapeItemNode(
                    $orig->keyName,
                    $orig->optional,
                    $this->resolveType($orig->valueType),
                );

            case $orig instanceof Type\ArrayShapeNode:
                return new Type\ArrayShapeNode(
                    array_map(fn(Type\ArrayShapeItemNode $item): Type\ArrayShapeItemNode => $this->resolveType($item), $orig->items),
                    $orig->sealed,
                    $orig->kind,
                );

            case $orig instanceof Type\ArrayTypeNode:
                return new Type\ArrayTypeNode(
                    $this->resolveType($orig->type),
                );

            case $orig instanceof Type\CallableTypeNode:
                return new Type\CallableTypeNode(
                    $this->resolveType($orig->identifier),
                    array_map(fn(Type\CallableTypeParameterNode $item): Type\CallableTypeParameterNode => $this->resolveType($item), $orig->parameters),
                    $this->resolveType($orig->returnType),
                    array_map(fn(TemplateTagValueNode $item): TemplateTagValueNode => $this->resolveType($item), $orig->templateTypes),
                );

            case $orig instanceof Type\ConditionalTypeForParameterNode:
                return new Type\ConditionalTypeForParameterNode(
                    $orig->parameterName,
                    $this->resolveType($orig->targetType),
                    $this->resolveType($orig->if),
                    $this->resolveType($orig->else),
                    $orig->negated,
                );

            case $orig instanceof Type\ConditionalTypeNode:
                return new Type\ConditionalTypeNode(
                    $this->resolveType($orig->subjectType),
                    $this->resolveType($orig->targetType),
                    $this->resolveType($orig->if),
                    $this->resolveType($orig->else),
                    $orig->negated,
                );

            case $orig instanceof Type\ConstTypeNode && $constExpr !== null:
                /**
                 * @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection PHPUnit is not able to handle coverage for match expressions correctly.
                 */
                switch (true) {
                    case $constExpr instanceof ConstExpr\ConstExprArrayItemNode:
                        return new Type\ConstTypeNode(
                            new ConstExpr\ConstExprArrayItemNode(
                                $this->resolveType($constExpr->key),
                                $this->resolveType($constExpr->value),
                            ),
                        );

                    case $constExpr instanceof ConstExpr\ConstExprArrayNode:
                        return new Type\ConstTypeNode(
                            new ConstExpr\ConstExprArrayNode(
                                array_map(fn(ConstExpr\ConstExprArrayItemNode $item): ConstExpr\ConstExprArrayItemNode => $this->resolveType($item), $constExpr->items),
                            ),
                        );

                    case $constExpr instanceof ConstExpr\ConstExprFalseNode:
                    case $constExpr instanceof ConstExpr\ConstExprFloatNode:
                    case $constExpr instanceof ConstExpr\ConstExprIntegerNode:
                    case $constExpr instanceof ConstExpr\ConstExprNullNode:
                    case $constExpr instanceof ConstExpr\ConstExprStringNode:
                    case $constExpr instanceof ConstExpr\ConstExprTrueNode:
                    case $constExpr instanceof ConstExpr\DoctrineConstExprStringNode:
                    case $constExpr instanceof ConstExpr\QuoteAwareConstExprStringNode:
                        return $orig;

                    case $constExpr instanceof ConstExpr\ConstFetchNode:
                        return new Type\ConstTypeNode(
                            new ConstExpr\ConstFetchNode(
                                $this->resolveIdentifier($constExpr->className),
                                $constExpr->name,
                            ),
                        );
                }
                throw new RuntimeException('Cannot resolve related types, expression is unsupported: ' . get_class($constExpr));

            case $orig instanceof Type\GenericTypeNode:
                return new Type\GenericTypeNode(
                    $this->resolveType($orig->type),
                    array_map(fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item), $orig->genericTypes),
                    $orig->variances,
                );

            case $orig instanceof Type\IdentifierTypeNode:
                return new Type\IdentifierTypeNode(
                    $this->resolveIdentifier($orig->name),
                );

            case $orig instanceof Type\IntersectionTypeNode:
                return new Type\IntersectionTypeNode(
                    array_map(fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item), $orig->types),
                );

            case $orig instanceof Type\NullableTypeNode:
                return new Type\NullableTypeNode(
                    $this->resolveType($orig->type),
                );

            case $orig instanceof Type\ObjectShapeItemNode:
                return new Type\ObjectShapeItemNode(
                    $orig->keyName,
                    $orig->optional,
                    $this->resolveType($orig->valueType),
                );

            case $orig instanceof Type\ObjectShapeNode:
                return new Type\ObjectShapeNode(
                    array_map(fn(Type\ObjectShapeItemNode $item): Type\ObjectShapeItemNode => $this->resolveType($item), $orig->items),
                );

            case $orig instanceof Type\OffsetAccessTypeNode:
                return new Type\OffsetAccessTypeNode(
                    $this->resolveType($orig->offset),
                    $this->resolveType($orig->type),
                );

            case $orig instanceof Type\ThisTypeNode:
                return new Type\IdentifierTypeNode(
                    $this->getScopeClass(),
                );

            case $orig instanceof Type\UnionTypeNode:
                return new Type\UnionTypeNode(
                    array_map(fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($item), $orig->types),
                );

            case $orig instanceof Type\CallableTypeParameterNode:
                return new Type\CallableTypeParameterNode(
                    $this->resolveType($orig->type),
                    $orig->isReference,
                    $orig->isVariadic,
                    $orig->parameterName,
                    $orig->isOptional,
                );

            case $orig instanceof TemplateTagValueNode:
                return new TemplateTagValueNode(
                    $orig->name,
                    $this->resolveType($orig->bound),
                    $orig->description ?: '',
                    $this->resolveType($orig->default),
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

        return $this->getScopeClass();
    }

    private function resolveBasicType(string $symbol): ?string
    {
        return in_array($symbol, self::BASIC_TYPES)
            ? $symbol
            : null;
    }

    private function resolveImportedType(string $symbol): ?string
    {
        if ($this->scopeFile === null) {
            return null;
        }

        $alias = $symbol;

        [$top, $rest] = explode('\\', $symbol, 2) + ['', ''];

        if ($top) {
            $alias = strtolower($top);
        }

        $aliases = $this->importsResolver->getImports($this->scopeFile);

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
        $namespace = $this->importsResolver->getNamespace($this->scopeFile ?? '');

        return $namespace ? "$namespace\\$symbol" : null;
    }

    /**
     * @return class-string
     */
    private function getScopeClass(): string
    {
        if ($this->scopeClass === null) {
            throw new RuntimeException('Current scope is not within any class');
        }

        return $this->scopeClass;
    }
}
