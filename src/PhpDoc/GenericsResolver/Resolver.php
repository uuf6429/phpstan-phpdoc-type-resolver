<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class Resolver
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
	 * @readonly
	 */
	private ResolverStateInterface $state;

	/**
	 * @param iterable<string, TypeNode> $templateTypesMap A map of <template type> => <concrete type> entries.
	 * @param iterable<string, TypeNode> $definedTypesMap A map of <template type> => <concrete type> entries.
	 * @param iterable<string, TypeNode> $importedTypesMap A map of <template type> => <concrete type> entries.
	 */
	public function __construct(
		iterable $templateTypesMap = [],
		iterable $definedTypesMap = [],
		iterable $importedTypesMap = [],
		?ResolverStateInterface $state = null,
	) {
		$this->templateTypesMap = is_array($templateTypesMap) ? $templateTypesMap : iterator_to_array($templateTypesMap);
		$this->definedTypesMap = is_array($definedTypesMap) ? $definedTypesMap : iterator_to_array($definedTypesMap);
		$this->importedTypesMap = is_array($importedTypesMap) ? $importedTypesMap : iterator_to_array($importedTypesMap);
		$this->state = $state ?? new ResolverValueState(isConcrete: true);
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
	 * Note: as a side effect, this records whether a template type stayed unresolved (i.e. was mapped to itself),
	 * which can then be queried via {@see self::hasUnresolvedTemplateType()}.
	 */
	public function map(string $template): null|TypeNode
	{
		$result = $this->getTemplateTypesMap()[$template]
			?? $this->getDefinedTypesMap()[$template]
			?? $this->getImportedTypesMap()[$template]
			?? null;

		if ((string)$result === $template) {
			$this->state->setConcrete(false);
		}

		return $result;
	}

	/**
	 * Returns true if, at any point, a template type stayed unresolved (i.e. was mapped to itself instead of a
	 * concrete type).
	 */
	public function hasUnresolvedTemplateType(): bool
	{
		return !$this->state->isConcrete();
	}

	public static function createMerged(Resolver $first, Resolver $second): self
	{
		return new self(
			array_merge($first->getTemplateTypesMap(), $second->getTemplateTypesMap()),
			array_merge($first->getDefinedTypesMap(), $second->getDefinedTypesMap()),
			array_merge($first->getImportedTypesMap(), $second->getImportedTypesMap()),
			new ResolverRefState([$first->state, $second->state]),
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
