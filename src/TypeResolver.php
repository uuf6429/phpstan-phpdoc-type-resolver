<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver;

use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr;
use PHPStan\PhpDocParser\Ast\PhpDoc;
use PHPStan\PhpDocParser\Ast\Type;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionException;
use RuntimeException;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory as PhpDocFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Extractor as GenericsExtractor;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Resolver as GenericsResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\ConcreteGenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateGenericTypeNode;
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
		'non-negative-int' => true ,
		'non-zero-int' => true,
		'class-string' => true,
		'callable-string' => true,
		'numeric-string' => true,
		'non-empty-string' => true,
		'non-falsy-string' => true ,
		'truthy-string' => true,
		'literal-string' => true,
		'void' => true ,
		'never' => true,
		'never-return' => true,
		'never-returns' => true,
		'no-return' => true,
		'int-mask' => true,
		'int-mask-of' => true,
		'key-of' => true ,
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
	 * @readonly
	 */
	private GenericsExtractor $genericsExtractor;

	/**
	 * @readonly
	 */
	private PhpImportsResolver $importsResolver;

	public function __construct(
		?GenericsExtractor $genericsExtractor = null,
		?PhpImportsResolver $importsResolver = null,
	) {
		$this->genericsExtractor = $genericsExtractor ?? new GenericsExtractor(new PhpDocFactory());
		$this->importsResolver = $importsResolver ?? new PhpImportsResolver();
	}

	public function resolve(Scope $scope, Type\TypeNode $type, ?GenericsResolver $genericResolver = null): Type\TypeNode
	{
		return $this->resolveType($scope, $type, $genericResolver ?? new GenericsResolver(), Type\TypeNode::class, false);
	}

	/**
	 * @template T of mixed
	 * @param class-string<T> $asClass
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
			$orig === null
			=> null,

			$orig instanceof Type\InvalidTypeNode
			=> $orig,

			$orig instanceof Type\ArrayShapeItemNode
			=> new Type\ArrayShapeItemNode(
				keyName: $orig->keyName,
				optional: $orig->optional,
				valueType: $this->resolveType($scope, $orig->valueType, $genericResolver, Type\TypeNode::class, false),
			),

			$orig instanceof Type\ArrayShapeNode
			=> new Type\ArrayShapeNode(
				items: array_map(
					fn(Type\ArrayShapeItemNode $item): Type\ArrayShapeItemNode => $this->resolveType($scope, $item, $genericResolver, Type\ArrayShapeItemNode::class, false),
					$orig->items,
				),
				sealed: $orig->sealed,
				kind: $orig->kind,
			),

			$orig instanceof Type\ArrayTypeNode
			=> new Type\ArrayTypeNode(
				type: $this->resolveType($scope, $orig->type, $genericResolver, Type\TypeNode::class, false),
			),

			$orig instanceof Type\CallableTypeNode
			=> new Type\CallableTypeNode(
				identifier: $this->resolveType($scope, $orig->identifier, $genericResolver, Type\IdentifierTypeNode::class, false),
				parameters: array_map(
					fn(Type\CallableTypeParameterNode $item): Type\CallableTypeParameterNode => $this->resolveType($scope, $item, $genericResolver, CallableTypeParameterNode::class, false),
					$orig->parameters,
				),
				returnType: $this->resolveType($scope, $orig->returnType, $genericResolver, Type\TypeNode::class, false),
				templateTypes: array_map(
					fn(PhpDoc\TemplateTagValueNode $item): PhpDoc\TemplateTagValueNode => $this->resolveType($scope, $item, $genericResolver, PhpDoc\TemplateTagValueNode::class, false),
					$orig->templateTypes,
				),
			),

			$orig instanceof Type\ConditionalTypeForParameterNode
			=> new Type\ConditionalTypeForParameterNode(
				parameterName: $orig->parameterName,
				targetType: $this->resolveType($scope, $orig->targetType, $genericResolver, Type\TypeNode::class, false),
				if: $this->resolveType($scope, $orig->if, $genericResolver, Type\TypeNode::class, false),
				else: $this->resolveType($scope, $orig->else, $genericResolver, Type\TypeNode::class, false),
				negated: $orig->negated,
			),

			$orig instanceof Type\ConditionalTypeNode
			=> new Type\ConditionalTypeNode(
				subjectType: $this->resolveType($scope, $orig->subjectType, $genericResolver, Type\TypeNode::class, false),
				targetType: $this->resolveType($scope, $orig->targetType, $genericResolver, Type\TypeNode::class, false),
				if: $this->resolveType($scope, $orig->if, $genericResolver, Type\TypeNode::class, false),
				else: $this->resolveType($scope, $orig->else, $genericResolver, Type\TypeNode::class, false),
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
						key: $this->resolveType($scope, $constExpr->key, $genericResolver, ConstExpr\ConstExprNode::class, true),
						value: $this->resolveType($scope, $constExpr->value, $genericResolver, ConstExpr\ConstExprNode::class, false),
					),
				),

				$constExpr instanceof ConstExpr\ConstExprArrayNode
				=> new Type\ConstTypeNode(
					constExpr: new ConstExpr\ConstExprArrayNode(
						items: array_map(
							fn(ConstExpr\ConstExprArrayItemNode $item): ConstExpr\ConstExprArrayItemNode => $this->resolveType($scope, $item, $genericResolver, ConstExpr\ConstExprArrayItemNode::class, false),
							$constExpr->items,
						),
					),
				),

				$constExpr instanceof ConstExpr\ConstFetchNode
				=> new Type\ConstTypeNode(
					constExpr: new ConstExpr\ConstFetchNode(
						className: ($resolved = $this->resolveIdentifier($scope, $constExpr->className, $genericResolver)) instanceof Type\IdentifierTypeNode
							? $resolved->name
							: throw new LogicException('Expected identifier node, but ' . get_debug_type($resolved) . ' was received'),
						name: $constExpr->name,
					),
				),

				default
				=> throw new RuntimeException('Cannot resolve related types, expression is unsupported: ' . get_class($constExpr)),
			},

			$orig instanceof Type\GenericTypeNode
			=> $this->resolveGenericType($scope, $orig, $genericResolver),

			$orig instanceof Type\IdentifierTypeNode
			=> $this->resolveIdentifier($scope, $orig->name, $genericResolver),

			$orig instanceof Type\IntersectionTypeNode
			=> new Type\IntersectionTypeNode(
				types: array_map(
					fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($scope, $item, $genericResolver, Type\TypeNode::class, false),
					$orig->types,
				),
			),

			$orig instanceof Type\NullableTypeNode
			=> new Type\NullableTypeNode(
				type: $this->resolveType($scope, $orig->type, $genericResolver, Type\TypeNode::class, false),
			),

			$orig instanceof Type\ObjectShapeItemNode
			=> new Type\ObjectShapeItemNode(
				keyName: $orig->keyName,
				optional: $orig->optional,
				valueType: $this->resolveType($scope, $orig->valueType, $genericResolver, Type\TypeNode::class, false),
			),

			$orig instanceof Type\ObjectShapeNode
			=> new Type\ObjectShapeNode(
				items: array_map(
					fn(Type\ObjectShapeItemNode $item): Type\ObjectShapeItemNode => $this->resolveType($scope, $item, $genericResolver, Type\ObjectShapeItemNode::class, false),
					$orig->items,
				),
			),

			$orig instanceof Type\OffsetAccessTypeNode
			=> new Type\OffsetAccessTypeNode(
				type: $this->resolveType($scope, $orig->offset, $genericResolver, Type\TypeNode::class, false),
				offset: $this->resolveType($scope, $orig->type, $genericResolver, Type\TypeNode::class, false),
			),

			$orig instanceof Type\ThisTypeNode
			=> $this->resolveRelativeTypes($scope, '$this')
				?? throw new LogicException('The `$this` type should always be resolved'),

			$orig instanceof Type\UnionTypeNode
			=> new Type\UnionTypeNode(
				types: array_map(
					fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($scope, $item, $genericResolver, Type\TypeNode::class, false),
					$orig->types,
				),
			),

			$orig instanceof Type\CallableTypeParameterNode
			=> new Type\CallableTypeParameterNode(
				type: $this->resolveType($scope, $orig->type, $genericResolver, Type\TypeNode::class, false),
				isReference: $orig->isReference,
				isVariadic: $orig->isVariadic,
				parameterName: $orig->parameterName,
				isOptional: $orig->isOptional,
			),

			$orig instanceof PhpDoc\TemplateTagValueNode
			=> new PhpDoc\TemplateTagValueNode(
				name: $orig->name,
				bound: $this->resolveType($scope, $orig->bound, $genericResolver, Type\TypeNode::class, true),
				description: $orig->description,
				default: $this->resolveType($scope, $orig->default, $genericResolver, Type\TypeNode::class, true),
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

	private function resolveGenericType(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): Type\GenericTypeNode
	{
		return isset(self::BASIC_TYPES[$orig->type->name])
			? $this->resolveGenericBasicType($scope, $orig, $genericResolver)
			: $this->resolveGenericClassType($scope, $orig, $genericResolver);
	}

	private function resolveGenericBasicType(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): TemplateGenericTypeNode|ConcreteGenericTypeNode
	{
		$isIntRange = isset(self::RANGE_TYPES[$orig->type->name]);
		$isGenericUtilityType = isset(self::GENERIC_UTILITY_TYPES[$orig->type->name]);

		$convertedType = $isGenericUtilityType
			? $orig->type
			: $this->resolveType($scope, $orig->type, $genericResolver, Type\IdentifierTypeNode::class, false);
		$convertedGenericTypes = array_map(
			fn(Type\TypeNode $item): Type\TypeNode =>
			($isIntRange && $item instanceof Type\IdentifierTypeNode && isset(self::RANGE_UTILITY_TYPES[$item->name]))
				? $item
				: $this->resolveType($scope, $item, $genericResolver, Type\TypeNode::class, false),
			$orig->genericTypes,
		);

		return $genericResolver->hasUnresolvedTemplateType()
			? new TemplateGenericTypeNode(
				type: $convertedType,
				templateTypes: $this->getOriginalTemplateTypes($orig, $convertedType),
				genericTypes: $convertedGenericTypes,
				variances: $orig->variances,
			)
			: new ConcreteGenericTypeNode(
				type: $convertedType,
				templateTypes: $this->getOriginalTemplateTypes($orig, $convertedType),
				genericTypes: $convertedGenericTypes,
				variances: $orig->variances,
			);
	}

	private function resolveGenericClassType(Scope $scope, Type\GenericTypeNode $orig, GenericsResolver $genericResolver): TemplateGenericTypeNode|ConcreteGenericTypeNode
	{
		$isGenericUtilityType = isset(self::GENERIC_UTILITY_TYPES[$orig->type->name]);
		$convertedType = $isGenericUtilityType
			? $orig->type
			: $this->resolveType($scope, $orig->type, $genericResolver, Type\IdentifierTypeNode::class, false);

		$convertedGenericTypes = array_map(
			fn(Type\TypeNode $item): Type\TypeNode => $this->resolveType($scope, $item, $genericResolver, Type\TypeNode::class, false),
			$orig->genericTypes,
		);

		if (!$isGenericUtilityType) {
			foreach ($convertedGenericTypes as $i => $type) {
				$genericResolver->setTemplateTypeAt($i, (string)$orig->genericTypes[$i], $type);
			}
		}

		return $genericResolver->hasUnresolvedTemplateType()
			? new TemplateGenericTypeNode(
				type: $convertedType,
				templateTypes: $this->getOriginalTemplateTypes($orig, $convertedType),
				genericTypes: $convertedGenericTypes,
				variances: $orig->variances,
			)
			: new ConcreteGenericTypeNode(
				type: $convertedType,
				templateTypes: $this->getOriginalTemplateTypes($orig, $convertedType),
				genericTypes: $convertedGenericTypes,
				variances: $orig->variances,
			);
	}

	/**
	 * @return list<Type\TypeNode>
	 * @throws ReflectionException
	 */
	private function getOriginalTemplateTypes(Type\GenericTypeNode $orig, TypeNode $convertedType): array
	{
		return match (true) {
			$orig->type->name === 'key-of',
			$orig->type->name === 'value-of',
			=> array_values($orig->genericTypes),

			$orig->type->name === 'int'
			=> match (count($orig->genericTypes)) {
				2 => [
					new Type\IdentifierTypeNode('$min'),
					new Type\IdentifierTypeNode('$max'),
				],
				default => throw new LogicException("Integer range type must have exactly 2 arguments: $orig"),
			},

			$orig->type->name === 'array',
			$orig->type->name === 'non-empty-array',
			=> match (count($orig->genericTypes)) {
				2 => [
					new Type\IdentifierTypeNode('$key'),
					new Type\IdentifierTypeNode('$value'),
				],
				1 => [
					new Type\IdentifierTypeNode('$value'),
				],
				default => throw new LogicException(ucfirst($orig->type->name) . " type cannot have more than 2 arguments: $orig"),
			},

			$orig->type->name === 'list',
			$orig->type->name === 'non-empty-list',
			$orig->type->name === 'iterable',
			=> match (count($orig->genericTypes)) {
				1 => [
					new Type\IdentifierTypeNode('$value'),
				],
				default => throw new LogicException(ucfirst($orig->type->name) . " type cannot have more than 1 argument: $orig"),
			},

			$orig->type->name === 'new',
			=> match (count($orig->genericTypes)) {
				1 => [
					new Type\IdentifierTypeNode('$class'),
				],
				default => throw new LogicException("New pseudo-type cannot have more than 1 argument: $orig"),
			},

			($convertedType instanceof Type\IdentifierTypeNode) && $this->isClassLike($convertedType->name)
			=> array_values(
				$this->genericsExtractor
					->extractFromClassName($convertedType->name)
					->getTemplateTypesMap(),
			),

			default => throw new LogicException("Cannot get original template types on type: $orig"),
		};
	}

	private function resolveIdentifier(Scope $scope, string $symbol, GenericsResolver $genericResolver): Type\TypeNode
	{
		return $this->resolveBasicType($symbol)
			?? $this->resolveVirtualOrGenericTypes($symbol, $genericResolver)
			?? $this->resolveRelativeTypes($scope, $symbol)
			?? $this->resolveImportedType($scope, $symbol)
			?? $this->resolveClassLike($symbol)
			?? $this->resolveNamespacedType($scope, $symbol)
			?? new Type\IdentifierTypeNode($symbol);
	}

	private function resolveVirtualOrGenericTypes(string $symbol, GenericsResolver $genericResolver): null|Type\TypeNode
	{
		return $genericResolver->map($symbol);
	}

	private function resolveRelativeTypes(Scope $scope, string $symbol): ?Type\IdentifierTypeNode
	{
		if (!isset(self::RELATIVE_TYPES[$symbol])) {
			return null;
		}

		if ($scope->class === null) {
			throw new LogicException("Cannot resolve `$symbol`, no class was defined in the current scope");
		}

		if ($symbol === 'parent') {
			return ($parent = get_parent_class($scope->class)) !== false
				? new Type\IdentifierTypeNode($parent)
				: throw new LogicException("Class/type `$scope->class` doesn't have a parent");
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

		if ($top !== '') {
			$alias = strtolower($top);
		}

		$aliases = $this->importsResolver->getImports($scope);

		if (!isset($aliases[$alias])) {
			return null;
		}

		if ($aliases[$alias] === $symbol) {
			return new Type\IdentifierTypeNode($symbol);
		}

		return new Type\IdentifierTypeNode(rtrim("$aliases[$alias]\\$rest", '\\'));
	}

	private function resolveNamespacedType(Scope $scope, string $symbol): ?Type\IdentifierTypeNode
	{
		return $this->resolveClassLike("{$this->importsResolver->getNamespace($scope)}\\$symbol");
	}

	private function resolveClassLike(string $symbol): ?Type\IdentifierTypeNode
	{
		return $this->isClassLike($symbol) ? new Type\IdentifierTypeNode($symbol) : null;
	}
}
