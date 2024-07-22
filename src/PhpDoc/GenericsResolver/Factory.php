<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Reflector;
use RuntimeException;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory as PhpDocFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\VirtualTypeNode;

class Factory
{
    /**
     * @var array<string, Resolver>
     */
    private array $cache = [];

    public function __construct(
        private readonly PhpDocFactory $factory,
    ) {
        //
    }

    public function extractFromReflector(Reflector $reflector): Resolver
    {
        return ($cacheKey = $this->makeCacheKey($reflector))
            ? ($this->cache[$cacheKey] ?? ($this->cache[$cacheKey] = $this->factory->createFromReflector($reflector)->getGenericResolver()))
            : $this->factory->createFromReflector($reflector)->getGenericResolver();
    }

    /**
     * @param null|class-string $currentClass
     */
    public function extractFromPhpDocNode(PhpDocNode $docNode, null|string $currentClass): Resolver
    {
        return new Resolver(
            [...$this->extractFromTemplateTags($docNode)],
            [...$this->extractFromTypeDefTags($docNode, $currentClass)],
            [...$this->extractFromTypeImportTags($docNode)],
        );
    }

    private function makeCacheKey(Reflector $reflector): ?string
    {
        return match (true) {
            $reflector instanceof ReflectionClass
            => $reflector->getName(),

            $reflector instanceof ReflectionMethod
            => "{$reflector->getDeclaringClass()->getName()}->{$reflector->getName()}",

            $reflector instanceof ReflectionFunction
            => $reflector->getName(),

            default
            => null,
        };
    }

    /**
     * @return iterable<string, IdentifierTypeNode>
     */
    private function extractFromTemplateTags(PhpDocNode $docNode): iterable
    {
        /** @var list<PhpDocTagNode<TemplateTagValueNode>> $tags */
        $tags = array_merge(
            $docNode->getTagsByName('@template'),
            $docNode->getTagsByName('@phpstan-template'),
        );
        foreach ($tags as $tag) {
            yield $tag->value->name => new IdentifierTypeNode((string)($tag->value->bound ?? $tag->value->name));
        }
    }

    /**
     * @param null|class-string $currentClass
     * @return iterable<string, VirtualTypeNode>
     */
    private function extractFromTypeDefTags(PhpDocNode $docNode, null|string $currentClass): iterable
    {
        /** @var list<PhpDocTagNode<TypeAliasTagValueNode>> $tags */
        $tags = $docNode->getTagsByName('@phpstan-type');
        if ($currentClass === null && count($tags)) {
            throw new RuntimeException('PHPStan local type requires a class');
        }
        foreach ($tags as $tag) {
            yield $tag->value->alias => new VirtualTypeNode(name: $tag->value->alias, type: $tag->value->type, declaringClass: $currentClass);
        }
    }

    /**
     * @return iterable<string, VirtualTypeNode>
     */
    private function extractFromTypeImportTags(PhpDocNode $docNode): iterable
    {
        /** @var list<PhpDocTagNode<TypeAliasImportTagValueNode>> $tags */
        $tags = $docNode->getTagsByName('@phpstan-import-type');
        foreach ($tags as $tag) {
            $name = $tag->value->importedAs ?? $tag->value->importedAlias;
            yield $name => new VirtualTypeNode(name: $name, type: new IdentifierTypeNode('TODO'), declaringClass: $tag->value->importedFrom->name); // TODO
        }
    }
}
