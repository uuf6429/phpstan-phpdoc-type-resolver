<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * A class representing a single parsed PHPDoc block element.
 */
class Block
{
    /**
     * @var null|list<string>
     */
    private null|array $genericTypes = null;

    /**
     * @param list<string> $inheritedGenericTypes
     */
    public function __construct(
        private readonly PhpDocNode   $docNode,
        private readonly TypeResolver $typeResolver,
        private readonly array $inheritedGenericTypes,
        private readonly GenericTypesExtractor $genericTypesExtractor,
    ) {
        //
    }

    public function getSummary(): string
    {
        foreach ($this->docNode->children as $child) {
            if (!$child instanceof PhpDocTextNode) {
                break;
            }

            if (trim($child->text) !== '') {
                return $child->text;
            }
        }

        return '';
    }

    public function getDescription(): string
    {
        $summaryFound = false;
        $descriptionLines = [];

        foreach ($this->docNode->children as $child) {
            if (!$child instanceof PhpDocTextNode) {
                break;
            }

            if (!$summaryFound) {
                $summaryFound = trim($child->text) !== '';
                continue;
            }

            $descriptionLines[] = $child->text;
        }

        return trim(implode("\n", $descriptionLines));
    }

    /**
     * @return list<PhpDocTagValueNode>
     */
    public function getTags(?string $name = null): array
    {
        return array_values(
            array_map(
                fn(PhpDocTagNode $tag): PhpDocTagValueNode => $this->resolveTypesInTag($tag->value),
                $name
                ? $this->docNode->getTagsByName($name)
                : $this->docNode->getTags(),
            ),
        );
    }

    /**
     * Returns a specific tag, throwing an exception if not found or multiple tags are found.
     * @throws TagNotFoundException|MultipleTagsFoundException
     */
    public function getTag(string $name): PhpDocTagValueNode
    {
        return $this->findTag($name) ?? throw new TagNotFoundException($name);
    }

    /**
     * Find and return a specific tag. If more than one tag is found, an exception is thrown.
     * If none are found, `null` is return.
     * @throws MultipleTagsFoundException
     */
    public function findTag(string $name): ?PhpDocTagValueNode
    {
        $tags = $this->docNode->getTagsByName($name);
        if (count($tags) > 1) {
            throw new MultipleTagsFoundException($name);
        }

        return $this->resolveTypesInTag(array_values($tags)[0]?->value ?? null);
    }

    public function hasTag(string $name): bool
    {
        foreach ($this->docNode->children as $child) {
            if ($child instanceof PhpDocTagNode && $child->name === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return ($tag is null ? null : PhpDocTagValueNode)
     */
    private function resolveTypesInTag(?PhpDocTagValueNode $tag): ?PhpDocTagValueNode
    {
        if (!$tag) {
            return null;
        }

        foreach (get_object_vars($tag) as $prop => $value) {
            if ($value instanceof TypeNode) {
                $tag->$prop = $this->typeResolver->resolve($value, $this->getGenericTypes());
            }
        }

        return $tag;
    }

    /**
     * @return list<string>
     */
    public function getGenericTypes(): array
    {
        return $this->genericTypes
            ?? ($this->genericTypes = array_merge(
                $this->inheritedGenericTypes,
                $this->genericTypesExtractor->extractFromPhpDocNode($this->docNode),
            ));
    }
}
