<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\IsClassLike;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory as PhpDocFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TypeDefTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * Extracts the generic/template, locally-defined and imported types declared by a PHPDoc block (or a reflected
 * class-like structure) into a {@see GenericTypeMap}.
 */
class Extractor
{
	use IsClassLike;

	/**
	 * @var array<string, GenericTypeMap>
	 */
	private array $cache = [];

	public function __construct(
		/** @readonly */
		private PhpDocFactory $factory,
	) {}

	/**
	 * @throws \ReflectionException
	 */
	public function extractFromClassName(string $className): GenericTypeMap
	{
		if (!$this->isClassLike($className)) {
			throw new \InvalidArgumentException("Class, interface, trait or enum does not exist: {$className}");
		}

		return $this->extractFromReflector(new \ReflectionClass($className));
	}

	/**
	 * @throws \ReflectionException
	 */
	public function extractFromReflector(\Reflector $reflector): GenericTypeMap
	{
		return ($cacheKey = $this->makeCacheKey($reflector)) !== null
			? ($this->cache[$cacheKey] ?? ($this->cache[$cacheKey] = $this->factory->createFromReflector($reflector)->getGenericsResolver()))
			: $this->factory->createFromReflector($reflector)->getGenericsResolver();
	}

	/**
	 * @param null|class-string $currentClass
	 *
	 * @throws \ReflectionException
	 */
	public function extractFromPhpDocNode(
		Scope $scope,
		PhpDocNode $docNode,
		?string $currentClass,
		TypeResolver $typeResolver,
		GenericTypeMap $genericsResolver,
	): GenericTypeMap {
		return new GenericTypeMap(
			$this->extractTemplateTags($scope, $docNode, $typeResolver, $genericsResolver),
			$this->extractTypeDefTags($scope, $docNode, $currentClass, $typeResolver, $genericsResolver),
			$this->extractTypeImportTags($scope, $docNode, $typeResolver, $genericsResolver),
		);
	}

	private function makeCacheKey(\Reflector $reflector): ?string
	{
		return match (true) {
			$reflector instanceof \ReflectionClass => $reflector->getName(),

			$reflector instanceof \ReflectionMethod => "{$reflector->getDeclaringClass()->getName()}->{$reflector->getName()}()",

			$reflector instanceof \ReflectionFunction => "{$reflector->getName()}()",

			default => null,
		};
	}

	/**
	 * @return iterable<string, TypeNode>
	 */
	private function extractTemplateTags(Scope $scope, PhpDocNode $docNode, TypeResolver $typeResolver, GenericTypeMap $genericsResolver): iterable
	{
		/** @var list<PhpDocTagNode<TemplateTagValueNode>> $tags */
		$tags = array_merge(
			$docNode->getTagsByName('@template'),
			$docNode->getTagsByName('@phpstan-template'),
		);
		foreach ($tags as $tag) {
			yield $tag->value->name => new TemplateTypeNode(
				name: $tag->value->name,
				bound: $tag->value->bound !== null
					? $typeResolver->resolve($scope, $tag->value->bound, $genericsResolver)
					: null,
			);
		}
	}

	/**
	 * @param null|class-string $currentClass
	 *
	 * @return iterable<string, TypeNode>
	 */
	private function extractTypeDefTags(Scope $scope, PhpDocNode $docNode, ?string $currentClass, TypeResolver $typeResolver, GenericTypeMap $genericsResolver): iterable
	{
		/** @var list<PhpDocTagNode<TypeAliasTagValueNode>> $tags */
		$tags = $docNode->getTagsByName('@phpstan-type');
		foreach ($tags as $tag) {
			yield $tag->value->alias => new TypeDefTypeNode(
				name: $tag->value->alias,
				type: $typeResolver->resolve($scope, $tag->value->type, $genericsResolver),
				declaringClass: $currentClass
				?? throw new \RuntimeException('PHPStan local type requires a class'),
			);
		}
	}

	/**
	 * @return iterable<string, TypeNode>
	 *
	 * @throws \ReflectionException
	 */
	private function extractTypeImportTags(Scope $scope, PhpDocNode $docNode, TypeResolver $typeResolver, GenericTypeMap $genericsResolver): iterable
	{
		/** @var list<PhpDocTagNode<TypeAliasImportTagValueNode>> $tags */
		$tags = $docNode->getTagsByName('@phpstan-import-type');
		foreach ($tags as $tag) {
			$name = $tag->value->importedAs ?? $tag->value->importedAlias;

			yield $name => new TypeDefTypeNode(
				name: $name,
				type: $this->getLocalTypeFromClass(
					$scope,
					$this->getNodeClass($typeResolver->resolve($scope, $tag->value->importedFrom, $genericsResolver)),
					$tag->value->importedAlias,
					$typeResolver,
					$genericsResolver,
				),
				declaringClass: $tag->value->importedFrom->name,
			);
		}
	}

	/**
	 * @param class-string $className
	 *
	 * @throws \ReflectionException
	 */
	private function getLocalTypeFromClass(Scope $scope, string $className, string $typeName, TypeResolver $typeResolver, GenericTypeMap $genericsResolver): TypeNode
	{
		$block = $this->factory->createFromReflector(new \ReflectionClass($className));

		/** @var list<TypeAliasTagValueNode> $tags */
		$tags = $block->getTags('@phpstan-type');
		foreach ($tags as $tag) {
			if ($tag->alias === $typeName) {
				return $typeResolver->resolve($scope, $tag->type, $genericsResolver);
			}
		}

		throw new \RuntimeException("A `@phpstan-type {$typeName}` PHPDoc tag was expected on class `{$className}`, but none was found");
	}

	/**
	 * @return class-string
	 */
	private function getNodeClass(TypeNode $node): string
	{
		if (!$node instanceof IdentifierTypeNode) {
			throw new \RuntimeException('PHPStan type import tag should point to an IdentifierTypeNode, got `' . get_debug_type($node) . '` instead');
		}

		if (!$this->isClassLike($node->name)) {
			throw new \RuntimeException("PHPStan type can only be imported from a simple class-like structure; symbol `{$node->name}` could not be found");
		}

		return $node->name;
	}
}
