<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class Resolver
{
    /**
     * @param array<string, TypeNode> $templateTypesMap A map of <template type> => <concrete type> entries.
     * @param array<string, TypeNode> $definedTypesMap A map of <template type> => <concrete type> entries.
     * @param array<string, TypeNode> $importedTypesMap A map of <template type> => <concrete type> entries.
     */
    public function __construct(
        private array $templateTypesMap = [],
        private array $definedTypesMap = [],
        private array $importedTypesMap = [],
        private readonly ResolverStateInterface $state = new ResolverValueState(isConcrete: true),
    ) {
        //
    }

    public function setTemplateType(string $template, TypeNode $concrete): void
    {
        $this->templateTypesMap[$template] = $concrete;
    }

    public function setTemplateTypeAt(int $index, TypeNode $concrete): void
    {
        $this->setTemplateType(array_keys($this->templateTypesMap)[$index], $concrete);
    }

    /**
     * Maps a template type to a concrete type, if possible.
     * Otherwise, returns the original template type if it's a known template type, or null if it isn't.
     */
    public function map(string $template): null|TypeNode
    {
        $result = $this->templateTypesMap[$template]
            ?? $this->definedTypesMap[$template]
            ?? $this->importedTypesMap[$template]
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
            array_merge($first->templateTypesMap, $second->templateTypesMap),
            array_merge($first->definedTypesMap, $second->definedTypesMap),
            array_merge($first->definedTypesMap, $second->definedTypesMap),
            new ResolverRefState([$first->state, $second->state]),
        );
    }
}
