<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver;

use PHPStan\PhpDocParser\Ast\ConstExpr;
use PHPStan\PhpDocParser\Ast\PhpDoc;
use PHPStan\PhpDocParser\Ast\Type;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory as PhpDocFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Extractor as GenericsExtractor;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\GenericTypeMap as GenericsResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\AbstractGenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\ConcreteGenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateGenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports\Resolver as PhpImportsResolver;

/**
 * This is the main class that resolves (fully-qualifies) PHPStan PHPDoc type objects. However, in practice you'd
 * probably want to use the {@see PhpDocFactory} class instead, since it provides useful helper methods.
 */
class TypeResolver
{
	use IsClassLike;

	/**
	 * @see https://phpstan.org/writing-php-code/phpdoc-types
	 *
	 * @var array<string, true>
	 */
	private const BASIC_TYPES = [
		'int' => true,
		'integer' => true,
		'string' => true,
		'array-key' => true,
		'bool' => true,
		'boolean' => true,
		'true' => true,
		'false' => true,
		'null' => true,
		'float' => true,
		'double' => true,
		'scalar' => true,
		'array' => true,
		'non-empty-array' => true,
		'list' => true,
		'non-empty-list' => true,
		'iterable' => true,
		'callable' => true,
		'pure-callable' => true,
		'pure-Closure' => true,
		'resource' => true,
		'closed-resource' => true,
		'open-resource' => true,
		'object' => true,
		'mixed' => true,
		'positive-int' => true,
		'negative-int' => true,
		'non-positive-int' => true,
		'non-negative-int' => true,
		'non-zero-int' => true,
		'class-string' => true,
		'callable-string' => true,
		'numeric-string' => true,
		'non-empty-string' => true,
		'non-falsy-string' => true,
		'truthy-string' => true,
		'literal-string' => true,
		'void' => true,
		'never' => true,
		'never-return' => true,
		'never-returns' => true,
		'no-return' => true,
		'int-mask' => true,
		'int-mask-of' => true,
		'key-of' => true,
		'value-of' => true,
	];

	/**
	 * @var array<string, true>
	 */
	private const RELATIVE_TYPES = [
		'self' => true,
		'static' => true,
		'$this' => true,
		'parent' => true,
	];

	/**
	 * @var array<string, true>
	 */
	private const RANGE_TYPES = [
		'int' => true,
	];

	/**
	 * @var array<string, true>
	 */
	private const RANGE_UTILITY_TYPES = [
		'min' => true,
		'max' => true,
	];

	/**
	 * @var array<string, true>
	 */
	private const GENERIC_UTILITY_TYPES = [
		'new' => true,
	];

	/**
	 * Pseudo-types whose template parameters are passed through verbatim as their "original" template types.
	 *
	 * @var array<string, true>
	 */
	private const KEY_VALUE_OF_TYPES = [
		'key-of' => true,
		'value-of' => true,
	];

	/**
	 * Array pseudo-types whose original template types are (optionally) a key and a value.
	 *
	 * @var array<string, true>
	 */
	private const KEYED_ARRAY_TYPES = [
		'array' => true,
		'non-empty-array' => true,
	];

	/**
	 * Iterable pseudo-types whose only original template type is a value.
	 *
	 * @var array<string, true>
	 */
	private const VALUE_ITERABLE_TYPES = [
		'list' => true,
		'non-empty-list' => true,
		'iterable' => true,
	];

	/**
	 * @readonly
	 */
	private GenericsExtractor $genericsExtractor;

	/**
	 * @readonly
	 */
	private PhpImportsResolver $importsResolver;

	public function __construct(
		GenericsExtractor $genericsExtractor,
		PhpImportsResolver $importsResolver,
	) {
		$this->genericsExtractor = $genericsExtractor;
		$this->importsResolver = $importsResolver;
	}

	public function resolve(Scope $scope, TypeNode $type, ?GenericsResolver $genericResolver = null): TypeNode
	{
		return $this->resolveType($scope, $type, $genericResolver ?? new GenericsResolver(), TypeNode::class, false);
	}

	/**
	 * @template T of mixed
	 *
	 * @param class-string<T> $asClass
	 *
	 * @return ($nullable is true ? ?T : T)
	 */
	private function resolveType(Scope $scope, mixed $orig, GenericsResolver $genericResolver, string $asClass, bool $nullable)
	{
		$constExpr = $orig instanceof Type\ConstTypeNode ? $orig->constExpr : null;
		if ($orig instanceof Type\CallableTypeNode) {
			foreach ($orig->templateTypes as $templateType) {
				$genericResolver->setTemplateType($templateType->name, new Type\IdentifierTypeNode($templateType->name)); // TODO what if name is actually not a template type?
			}
		}

		$result = match (true) {
			null === $orig => null,

			$orig instanceof Type\InvalidTypeNode => $orig,

			$orig instanceof Type\ArrayShapeItemNode => new Type\ArrayShapeItemNode(
				keyName: $orig->keyName,
				optional: $orig->optional,
				valueType: $this->resolveType($scope, $orig->valueType, $genericResolver, TypeNode::class, false),
			),

			$orig instanceof Type\ArrayShapeNode => new Type\ArrayShapeNode(
				items: array_map(
					fn(Type\ArrayShapeItemNode $item): Type\ArrayShapeItemNode => $this->resolveType($scope, $item, $genericResolver, Type\ArrayShapeItemNode::class, false),
					$orig->items,
				),
				sealed: $orig->sealed,
				kind: $orig->kind,
			),

			$orig instanceof Type\ArrayTypeNode => new Type\ArrayTypeNode(
				type: $this->resolveType($scope, $orig->type, $genericResolver, TypeNode::class, false),
			),

			$orig instanceof Type\CallableTypeNode => new Type\CallableTypeNode(
				identifier: $this->resolveType($scope, $orig->identifier, $genericResolver, Type\IdentifierTypeNode::class, false),
				parameters: array_map(
					fn(CallableTypeParameterNode $item): CallableTypeParameterNode => $this->resolveType($scope, $item, $genericResolver, CallableTypeParameterNode::class, false),
					$orig->parameters,
				),
				returnType: $this->resolveType($scope, $orig->returnType, $genericResolver, TypeNode::class, false),
				templateTypes: array_map(
					fn(PhpDoc\TemplateTagValueNode $item): PhpDoc\TemplateTagValueNode => $this->resolveType($scope, $item, $genericResolver, PhpDoc\TemplateTagValueNode::class, false),
					$orig->templateTypes,
				),
			),

			$orig instanceof Type\ConditionalTypeForParameterNode => new Type\ConditionalTypeForParameterNode(
				parameterName: $orig->parameterName,
				targetType: $this->resolveType($scope, $orig->targetType, $genericResolver, TypeNode::class, false),
				if: $this->resolveType($scope, $orig->if, $genericResolver, TypeNode::class, false),
				else: $this->resolveType($scope, $orig->else, $genericResolver, TypeNode::class, false),
				negated: $orig->negated,
			),

			$orig instanceof Type\ConditionalTypeNode => new Type\ConditionalTypeNode(
				subjectType: $this->resolveType($scope, $orig->subjectType, $genericResolver, TypeNode::class, false),
				targetType: $this->resolveType($scope, $orig->targetType, $genericResolver, TypeNode::class, false),
				if: $this->resolveType($scope, $orig->if, $genericResolver, TypeNode::class, false),
				else: $this->resolveType($scope, $orig->else, $genericResolver, TypeNode::class, false),
				negated: $orig->negated,
			),

			$orig instanceof Type\ConstTypeNode => match (true) {
				null === $constExpr,
				$constExpr instanceof ConstExpr\ConstExprFalseNode,
				$constExpr instanceof ConstExpr\ConstExprFloatNode,
				$constExpr instanceof ConstExpr\ConstExprIntegerNode,
				$constExpr instanceof ConstExpr\ConstExprNullNode,
				$constExpr instanceof ConstExpr\ConstExprStringNode,
				$constExpr instanceof ConstExpr\ConstExprTrueNode => $orig,

				$constExpr instanceof ConstExpr\ConstExprArrayItemNode => new Type\ConstTypeNode(
					constExpr: new ConstExpr\ConstExprArrayItemNode(
						key: $this->resolveType($scope, $constExpr->key, $genericResolver, ConstExpr\ConstExprNode::class, true),
						value: $this->resolveType($scope, $constExpr->value, $genericResolver, ConstExpr\ConstExprNode::class, false),
					),
				),

				$constExpr instanceof ConstExpr\ConstExprArrayNode => new Type\ConstTypeNode(
					constExpr: new ConstExpr\ConstExprArrayNode(
						items: array_map(
							fn(ConstExpr\ConstExprArrayItemNode $item): ConstExpr\ConstExprArrayItemNode => $this->resolveType($scope, $item, $genericResolver, ConstExpr\ConstExprArrayItemNode::class, false),
							$constExpr->items,
						),
					),
				),

				$constExpr instanceof ConstExpr\ConstFetchNode => new Type\ConstTypeNode(
					constExpr: new ConstExpr\ConstFetchNode(
						className: ($resolved = $this->resolveIdentifier($scope, $constExpr->className, $genericResolver)) instanceof Type\IdentifierTypeNode
							? $resolved->name
							: throw new \LogicException('Expected identifier node, but ' . get_debug_type($resolved) . ' was received'),
						name: $constExpr->name,
					),
				),

				default => throw new \RuntimeException('Cannot resolve related types, expression is unsupported: ' . \get_class($constExpr)),
			},

			$orig instanceof Type\GenericTypeNode => $this->resolveGenericType($scope, $orig, $genericResolver),

			$orig instanceof Type\IdentifierTypeNode => $this->resolveIdentifier($scope, $orig->name, $genericResolver),

			$orig instanceof Type\IntersectionTypeNode => new Type\IntersectionTypeNode(
				types: array_map(
					fn(TypeNode $item): TypeNode => $this->resolveType($scope, $item, $genericResolver, TypeNode::class, false),
					$orig->types,
				),
			),

			$orig instanceof Type\NullableTypeNode => new Type\NullableTypeNode(
				type: $this->resolveType($scope, $orig->type, $genericResolver, TypeNode::class, false),
			),

			$orig instanceof Type\ObjectShapeItemNode => new Type\ObjectShapeItemNode(
				keyName: $orig->keyName,
				optional: $orig->optional,
				valueType: $this->resolveType($scope, $orig->valueType, $genericResolver, TypeNode::class, false),
			),

			$orig instanceof Type\ObjectShapeNode => new Type\ObjectShapeNode(
				items: array_map(
					fn(Type\ObjectShapeItemNode $item): Type\ObjectShapeItemNode => $this->resolveType($scope, $item, $genericResolver, Type\ObjectShapeItemNode::class, false),
					$orig->items,
				),
			),

			$orig instanceof Type\OffsetAccessTypeNode => new Type\OffsetAccessTypeNode(
				type: $this->resolveType($scope, $orig->offset, $genericResolver, TypeNode::class, false),
				offset: $this->resolveType($scope, $orig->type, $genericResolver, TypeNode::class, false),
			),

			$orig instanceof Type\ThisTypeNode => $this->resolveRelativeTypes($scope, '$this')
				?? throw new \LogicException('The `$this` type should always be resolved'),

			$orig instanceof Type\UnionTypeNode => new Type\UnionTypeNode(
				types: array_map(
					fn(TypeNode $item): TypeNode => $this->resolveType($scope, $item, $genericResolver, TypeNode::class, false),
					$orig->types,
				),
			),

			$orig instanceof CallableTypeParameterNode => new CallableTypeParameterNode(
				type: $this->resolveType($scope, $orig->type, $genericResolver, TypeNode::class, false),
				isReference: $orig->isReference,
				isVariadic: $orig->isVariadic,
				parameterName: $orig->parameterName,
				isOptional: $orig->isOptional,
			),

			$orig instanceof PhpDoc\TemplateTagValueNode => new PhpDoc\TemplateTagValueNode(
				name: $orig->name,
				bound: $this->resolveType($scope, $orig->bound, $genericResolver, TypeNode::class, true),
				description: $orig->description,
				default: $this->resolveType($scope, $orig->default, $genericResolver, TypeNode::class, true),
			),

			default => throw new \RuntimeException('Cannot resolve related types, type is unsupported: ' . get_debug_type($orig)),
		};

		\assert(
			($nullable && null === $result) || (\is_object($result) && is_a($result, $asClass)),
			'Expected a result of ' . ($nullable ? "?{$asClass}" : $asClass) . ' but got ' . get_debug_type($result) . ' instead',
		);

		return $result;
	}

	private function resolveGenericType(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): Type\GenericTypeNode
	{
		return isset(self::BASIC_TYPES[$orig->type->name])
			? $this->resolveGenericBasicType($scope, $orig, $genericResolver)
			: $this->resolveGenericClassType($scope, $orig, $genericResolver);
	}

	/**
	 * @throws \ReflectionException
	 */
	private function resolveGenericBasicType(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): AbstractGenericTypeNode
	{
		$isIntRange = isset(self::RANGE_TYPES[$orig->type->name]);

		$convertedType = $this->resolveGenericTypeName($scope, $orig, $genericResolver);
		$convertedGenericTypes = array_map(
			fn(TypeNode $item): TypeNode => ($isIntRange && $item instanceof Type\IdentifierTypeNode && isset(self::RANGE_UTILITY_TYPES[$item->name]))
				? $item
				: $this->resolveType($scope, $item, $genericResolver, TypeNode::class, false),
			$orig->genericTypes,
		);

		return $this->buildGenericNode(
			$genericResolver->hasUnresolvedTemplateType(),
			$convertedType,
			$orig,
			array_values($convertedGenericTypes),
		);
	}

	private function resolveGenericClassType(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): AbstractGenericTypeNode
	{
		$isGenericUtilityType = isset(self::GENERIC_UTILITY_TYPES[$orig->type->name]);

		$convertedType = $this->resolveGenericTypeName($scope, $orig, $genericResolver);
		$convertedGenericTypes = array_values(array_map(
			fn(TypeNode $item): TypeNode => $this->resolveType($scope, $item, $genericResolver, TypeNode::class, false),
			$orig->genericTypes,
		));

		if (!$isGenericUtilityType) {
			foreach ($convertedGenericTypes as $i => $type) {
				assert(isset($orig->genericTypes[$i]));
				$genericResolver->setTemplateTypeAt($i, (string)$orig->genericTypes[$i], $type);
			}
		}

		return $this->buildGenericNode(
			$genericResolver->hasUnresolvedTemplateType(),
			$convertedType,
			$orig,
			$convertedGenericTypes,
		);
	}

	/**
	 * Resolves the generic's outer type name, leaving generic utility types (e.g. `new`) untouched.
	 */
	private function resolveGenericTypeName(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): Type\IdentifierTypeNode
	{
		return isset(self::GENERIC_UTILITY_TYPES[$orig->type->name])
			? $orig->type
			: $this->resolveType($scope, $orig->type, $genericResolver, Type\IdentifierTypeNode::class, false);
	}

	/**
	 * Builds the resolved generic node, picking the {@see TemplateGenericTypeNode}/{@see ConcreteGenericTypeNode}
	 * marker subclass depending on whether any template type stayed unresolved.
	 *
	 * @param list<TypeNode> $convertedGenericTypes
	 *
	 * @throws \ReflectionException
	 */
	private function buildGenericNode(bool $isTemplate, Type\IdentifierTypeNode $convertedType, Type\GenericTypeNode $orig, array $convertedGenericTypes): AbstractGenericTypeNode
	{
		$templateTypes = $this->getOriginalTemplateTypes($orig, $convertedType);

		return $isTemplate
			? new TemplateGenericTypeNode(
				type: $convertedType,
				templateTypes: $templateTypes,
				genericTypes: $convertedGenericTypes,
				variances: array_values($orig->variances),
			)
			: new ConcreteGenericTypeNode(
				type: $convertedType,
				templateTypes: $templateTypes,
				genericTypes: $convertedGenericTypes,
				variances: array_values($orig->variances),
			);
	}

	/**
	 * @return list<TypeNode>
	 *
	 * @throws \ReflectionException
	 */
	private function getOriginalTemplateTypes(Type\GenericTypeNode $orig, TypeNode $convertedType): array
	{
		return match (true) {
			isset(self::KEY_VALUE_OF_TYPES[$orig->type->name]) => array_values($orig->genericTypes),

			isset(self::RANGE_TYPES[$orig->type->name]) => match (\count($orig->genericTypes)) {
				2 => [
					new Type\IdentifierTypeNode('$min'),
					new Type\IdentifierTypeNode('$max'),
				],
				default => throw new \LogicException("Integer range type must have exactly 2 arguments: {$orig}"),
			},

			isset(self::KEYED_ARRAY_TYPES[$orig->type->name]) => match (\count($orig->genericTypes)) {
				2 => [
					new Type\IdentifierTypeNode('$key'),
					new Type\IdentifierTypeNode('$value'),
				],
				1 => [
					new Type\IdentifierTypeNode('$value'),
				],
				default => throw new \LogicException(ucfirst($orig->type->name) . " type cannot have more than 2 arguments: {$orig}"),
			},

			isset(self::VALUE_ITERABLE_TYPES[$orig->type->name]) => match (\count($orig->genericTypes)) {
				1 => [
					new Type\IdentifierTypeNode('$value'),
				],
				default => throw new \LogicException(ucfirst($orig->type->name) . " type cannot have more than 1 argument: {$orig}"),
			},

			isset(self::GENERIC_UTILITY_TYPES[$orig->type->name]) => match (\count($orig->genericTypes)) {
				1 => [
					new Type\IdentifierTypeNode('$class'),
				],
				default => throw new \LogicException("New pseudo-type cannot have more than 1 argument: {$orig}"),
			},

			($convertedType instanceof Type\IdentifierTypeNode) && $this->isClassLike($convertedType->name) => array_values(
				$this->genericsExtractor
					->extractFromClassName($convertedType->name)
					->getTemplateTypesMap(),
			),

			default => throw new \LogicException("Cannot get original template types on type: {$orig}"),
		};
	}

	private function resolveIdentifier(Scope $scope, string $symbol, GenericsResolver $genericResolver): TypeNode
	{
		return $this->resolveBasicType($symbol)
			?? $this->resolveVirtualOrGenericTypes($symbol, $genericResolver)
			?? $this->resolveRelativeTypes($scope, $symbol)
			?? $this->resolveImportedType($scope, $symbol)
			?? $this->resolveClassLike($symbol)
			?? $this->resolveNamespacedType($scope, $symbol)
			?? new Type\IdentifierTypeNode($symbol);
	}

	private function resolveVirtualOrGenericTypes(string $symbol, GenericsResolver $genericResolver): ?TypeNode
	{
		$result = $genericResolver->map($symbol);

		if ($this->isUnresolvedTemplateReference($result, $symbol)) {
			$genericResolver->markTemplateTypeUnresolved();
		}

		return $result;
	}

	/**
	 * Determines whether a mapped type is really just the template referring back to itself (i.e. it stayed
	 * unresolved instead of being mapped to a concrete type). This mirrors the previous `(string) === $symbol`
	 * heuristic, but inspects the node structure explicitly instead of relying on its string representation.
	 */
	private function isUnresolvedTemplateReference(?TypeNode $result, string $symbol): bool
	{
		return match (true) {
			$result instanceof TemplateTypeNode => null === $result->bound && $result->name === $symbol,
			$result instanceof Type\IdentifierTypeNode => $result->name === $symbol,
			default => false,
		};
	}

	private function resolveRelativeTypes(Scope $scope, string $symbol): ?Type\IdentifierTypeNode
	{
		if (!isset(self::RELATIVE_TYPES[$symbol])) {
			return null;
		}

		if (null === $scope->class) {
			throw new \LogicException("Cannot resolve `{$symbol}`, no class was defined in the current scope");
		}

		if ('parent' === $symbol) {
			return ($parent = get_parent_class($scope->class)) !== false
				? new Type\IdentifierTypeNode($parent)
				: throw new \LogicException("Class/type `{$scope->class}` doesn't have a parent");
		}

		return new Type\IdentifierTypeNode($scope->class);
	}

	private function resolveBasicType(string $symbol): ?Type\IdentifierTypeNode
	{
		return isset(self::BASIC_TYPES[$symbol])
			? new Type\IdentifierTypeNode($symbol)
			: null;
	}

	private function resolveImportedType(Scope $scope, string $symbol): ?Type\IdentifierTypeNode
	{
		$alias = $symbol;

		[$top, $rest] = explode('\\', $symbol, 2) + ['', ''];

		if ('' !== $top) {
			$alias = strtolower($top);
		}

		$aliases = $this->importsResolver->getImports($scope);

		if (!isset($aliases[$alias])) {
			return null;
		}

		if ($aliases[$alias] === $symbol) {
			return new Type\IdentifierTypeNode($symbol);
		}

		return new Type\IdentifierTypeNode(rtrim("{$aliases[$alias]}\\{$rest}", '\\'));
	}

	private function resolveNamespacedType(Scope $scope, string $symbol): ?Type\IdentifierTypeNode
	{
		return $this->resolveClassLike("{$this->importsResolver->getNamespace($scope)}\\{$symbol}");
	}

	private function resolveClassLike(string $symbol): ?Type\IdentifierTypeNode
	{
		return $this->isClassLike($symbol) ? new Type\IdentifierTypeNode($symbol) : null;
	}
}
