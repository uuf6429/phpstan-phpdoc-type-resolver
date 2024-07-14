<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

class Block
{
    public function __construct(
        private readonly PhpDocNode   $docNode,
        private readonly TypeResolver $typeResolver,
    ) {
        //
    }

    /**
     * @return list<PhpDocTagValueNode>
     */
    public function getTags(?string $name = null): array
    {
        return array_map(
            fn(PhpDocTagNode $tag): PhpDocTagValueNode => $this->resolveTypesInTag($tag->value),
            $name
                ? $this->docNode->getTagsByName($name)
                : $this->docNode->getTags(),
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

        return $this->resolveTypesInTag($tags[0]?->value ?? null);
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
                $tag->$prop = $this->typeResolver->resolve($value);
            }
        }

        return $tag;
    }
}
