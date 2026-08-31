<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Holds the generic/template, defined and imported type names available within a scope, and resolves a name to its
 * concrete type via {@see self::map()}. It also tracks whether every template type seen so far stayed concrete.
 */
class GenericTypeMap
{
	/**
	 * @var array<string, TypeNode>
	 */
	private array $templateTypesMap;

	/**
	 * @var array<string, TypeNode>
	 */
	private array $definedTypesMap;

	/**
	 * @var array<string, TypeNode>
	 */
	private array $importedTypesMap;

	/**
	 * Tracks whether every template type seen so far resolved to a concrete type. It stays set while everything is
	 * concrete and gets unset (via {@see self::markTemplateTypeUnresolved()}) as soon as a template type stays
	 * unresolved (i.e. was mapped to itself).
	 *
	 * @readonly
	 */
	private Flag $concreteness;

	/**
	 * @param iterable<string, TypeNode> $templateTypesMap a map of <template type> => <concrete type> entries
	 * @param iterable<string, TypeNode> $definedTypesMap  a map of <template type> => <concrete type> entries
	 * @param iterable<string, TypeNode> $importedTypesMap a map of <template type> => <concrete type> entries
	 */
	public function __construct(
		iterable $templateTypesMap = [],
		iterable $definedTypesMap = [],
		iterable $importedTypesMap = [],
		?Flag $concreteness = null,
	) {
		$this->templateTypesMap = \is_array($templateTypesMap) ? $templateTypesMap : iterator_to_array($templateTypesMap);
		$this->definedTypesMap = \is_array($definedTypesMap) ? $definedTypesMap : iterator_to_array($definedTypesMap);
		$this->importedTypesMap = \is_array($importedTypesMap) ? $importedTypesMap : iterator_to_array($importedTypesMap);
		$this->concreteness = $concreteness ?? new SimpleFlag(true);
	}

	public function setTemplateType(string $template, TypeNode $concrete): void
	{
		$this->templateTypesMap[$template] = $concrete;
	}

	public function setTemplateTypeAt(int $index, string $templateFallback, TypeNode $concrete): void
	{
		$this->setTemplateType(array_keys($this->getTemplateTypesMap())[$index] ?? $templateFallback, $concrete);
	}

	/**
	 * Maps a template type to a concrete type, if possible.
	 * Otherwise, returns the original template type if it's a known template type, or null if it isn't.
	 *
	 * This is a pure lookup with no side effects. Whether a template type stayed unresolved (i.e. was mapped to
	 * itself) must be recorded explicitly via {@see self::markTemplateTypeUnresolved()} and can then be queried via
	 * {@see self::hasUnresolvedTemplateType()}.
	 */
	public function map(string $template): ?TypeNode
	{
		return $this->getTemplateTypesMap()[$template]
			?? $this->getDefinedTypesMap()[$template]
			?? $this->getImportedTypesMap()[$template]
			?? null;
	}

	/**
	 * Records that a template type stayed unresolved (i.e. was mapped to itself instead of a concrete type), which
	 * can then be queried via {@see self::hasUnresolvedTemplateType()}.
	 */
	public function markTemplateTypeUnresolved(): void
	{
		$this->concreteness->set(false);
	}

	/**
	 * Returns true if, at any point, a template type stayed unresolved (i.e. was mapped to itself instead of a
	 * concrete type).
	 */
	public function hasUnresolvedTemplateType(): bool
	{
		return !$this->concreteness->isSet();
	}

	public static function createMerged(self $first, self $second): self
	{
		return new self(
			array_merge($first->getTemplateTypesMap(), $second->getTemplateTypesMap()),
			array_merge($first->getDefinedTypesMap(), $second->getDefinedTypesMap()),
			array_merge($first->getImportedTypesMap(), $second->getImportedTypesMap()),
			new AggregateFlag([$first->concreteness, $second->concreteness]),
		);
	}

	/**
	 * @return array<string, TypeNode>
	 */
	public function getTemplateTypesMap(): array
	{
		return $this->templateTypesMap;
	}

	/**
	 * @return array<string, TypeNode>
	 */
	public function getDefinedTypesMap(): array
	{
		return $this->definedTypesMap;
	}

	/**
	 * @return array<string, TypeNode>
	 */
	public function getImportedTypesMap(): array
	{
		return $this->importedTypesMap;
	}
}
