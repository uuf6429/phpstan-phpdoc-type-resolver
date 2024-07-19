<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use Reflector;

class GenericTypesExtractor
{
    public function __construct(
        private readonly Factory $factory,
    ) {
        //
    }

    /**
     * @return list<string>
     */
    public function extractFromReflector(Reflector $reflector): array
    {
        return $this->factory->createFromReflector($reflector)->getGenericTypes();
    }

    /**
     * @return list<string>
     */
    public function extractFromPhpDocNode(PhpDocNode $docNode): array
    {
        return array_filter(
            array_unique(
                array_merge(
                    $this->extractFromTemplateTags($docNode),
                    $this->extractFromTypeDefTags($docNode),
                    $this->extractFromTypeImportTags($docNode),
                ),
            ),
        );
    }

    /**
     * @return list<null|string>
     */
    private function extractFromTemplateTags(PhpDocNode $docNode): array
    {
        return array_filter(array_map(
            static fn(PhpDocTagNode $tag): ?string => $tag->value instanceof TemplateTagValueNode
                ? $tag->value->name : null,
            array_merge(
                $docNode->getTagsByName('@template'),
                $docNode->getTagsByName('@psalm-template'),
                $docNode->getTagsByName('@phpstan-template'),
            ),
        ));
    }

    /**
     * @return list<null|string>
     */
    private function extractFromTypeDefTags(PhpDocNode $docNode): array
    {
        return array_filter(array_map(
            static fn(PhpDocTagNode $tag): ?string => $tag->value instanceof TypeAliasTagValueNode
                ? $tag->value->alias : null,
            array_merge(
                $docNode->getTagsByName('@type'),
                $docNode->getTagsByName('@psalm-type'),
                $docNode->getTagsByName('@phpstan-type'),
            ),
        ));
    }

    /**
     * @return list<null|string>
     */
    private function extractFromTypeImportTags(PhpDocNode $docNode): array
    {
        return array_filter(array_map(
            static fn(PhpDocTagNode $tag): ?string => $tag->value instanceof TypeAliasImportTagValueNode
                ? ($tag->value->importedAs ?? $tag->value->importedAlias) : null,
            array_merge(
                $docNode->getTagsByName('@import-type'),
                $docNode->getTagsByName('@psalm-import-type'),
                $docNode->getTagsByName('@phpstan-import-type'),
            ),
        ));
    }
}
