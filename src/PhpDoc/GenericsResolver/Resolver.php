<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class Resolver
{
    /**
     * @var array<string, TypeNode>
     */
    private array $templateTypesMapCache;

    /**
     * @var array<string, TypeNode>
     */
    private array $definedTypesMapCache;

    /**
     * @var array<string, TypeNode>
     */
    private array $importedTypesMapCache;

    /**
     * @param iterable<string, TypeNode> $templateTypesMap A map of <template type> => <concrete type> entries.
     * @param iterable<string, TypeNode> $definedTypesMap A map of <template type> => <concrete type> entries.
     * @param iterable<string, TypeNode> $importedTypesMap A map of <template type> => <concrete type> entries.
     */
    public function __construct(
        private readonly iterable $templateTypesMap = [],
        private readonly iterable $definedTypesMap = [],
        private readonly iterable $importedTypesMap = [],
        private readonly ResolverStateInterface $state = new ResolverValueState(isConcrete: true),
    ) {
        //
    }

    public function setTemplateType(string $template, TypeNode $concrete): void
    {
        $this->getTemplateTypesMap();
        $this->templateTypesMapCache[$template] = $concrete;
    }

    public function setTemplateTypeAt(int $index, TypeNode $concrete): void
    {
        $this->setTemplateType(array_keys($this->getTemplateTypesMap())[$index], $concrete);
    }

    /**
     * Maps a template type to a concrete type, if possible.
     * Otherwise, returns the original template type if it's a known template type, or null if it isn't.
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
     * Returns true if at any point, a template type was mapped to itself.
     */
    public function hasMappedTemplateType(): bool
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
    private function getTemplateTypesMap(): array
    {
        return $this->templateTypesMapCache
            ?? ($this->templateTypesMapCache = [...$this->templateTypesMap]);
    }

    /**
     * @return array<string, TypeNode>
     */
    private function getDefinedTypesMap(): array
    {
        return $this->definedTypesMapCache
            ?? ($this->definedTypesMapCache = [...$this->definedTypesMap]);
    }

    /**
     * @return array<string, TypeNode>
     */
    private function getImportedTypesMap(): array
    {
        return $this->importedTypesMapCache
            ?? ($this->importedTypesMapCache = [...$this->importedTypesMap]);
    }
}
