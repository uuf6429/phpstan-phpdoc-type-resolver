<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\VirtualTypeNode;

class Resolver
{
    /**
     * @param array<string, string> $templateTypesMap A map of <template type> => <concrete type> entries.
     * @param array<string, VirtualTypeNode> $definedTypesMap A map of <template type> => <concrete type> entries.
     * @param array<string, VirtualTypeNode> $importedTypesMap A map of <template type> => <concrete type> entries.
     */
    public function __construct(
        private array $templateTypesMap = [],
        private array $definedTypesMap = [],
        private array $importedTypesMap = [],
        private readonly ResolverStateInterface $state = new ResolverValueState(isConcrete: true),
    ) {
        //
    }

    public function setTemplateType(string $template, string $concrete): void
    {
        $this->templateTypesMap[$template] = $concrete;
    }

    /**
     * Maps a template type to a concrete type, if possible.
     * Otherwise, returns the original template type if it's a known template type, or null if it isn't.
     */
    public function map(string $template): null|string|VirtualTypeNode
    {
        $result = $this->templateTypesMap[$template]
            ?? $this->definedTypesMap[$template]
            ?? $this->importedTypesMap[$template]
            ?? null;

        if ($result === $template) {
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

    public static function createCombined(Resolver $keys, Resolver $values): self
    {
        return new self(
            array_combine(array_keys($keys->templateTypesMap), array_values($values->templateTypesMap)),
            array_combine(array_keys($keys->definedTypesMap), array_values($values->definedTypesMap)),
            array_combine(array_keys($keys->importedTypesMap), array_values($values->importedTypesMap)),
            $values->state,
        );
    }
}
