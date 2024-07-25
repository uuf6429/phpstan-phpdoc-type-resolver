<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Reflector;
use RuntimeException;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory as PhpDocFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TypeDefTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

class Factory
{
    /**
     * @var array<string, Resolver>
     */
    private array $cache = [];

    public function __construct(
        private readonly PhpDocFactory     $factory,
        private readonly null|TypeResolver $typeResolver = null,
        private readonly null|Resolver $genericsResolver = null,
    ) {
        //
    }

    public function withResolvers(TypeResolver $typeResolver, Resolver $genericsResolver): self
    {
        return new self($this->factory, $typeResolver, $genericsResolver);
    }

    /**
     * @throws ReflectionException
     */
    public function extractFromReflector(Reflector $reflector): Resolver
    {
        return ($cacheKey = $this->makeCacheKey($reflector))
            ? ($this->cache[$cacheKey] ?? ($this->cache[$cacheKey] = $this->factory->createFromReflector($reflector)->getGenericsResolver()))
            : $this->factory->createFromReflector($reflector)->getGenericsResolver();
    }

    /**
     * @param null|class-string $currentClass
     * @throws ReflectionException
     */
    public function extractFromPhpDocNode(PhpDocNode $docNode, null|string $currentClass): Resolver
    {
        return new Resolver(
            $this->getExtractorForTemplateTags($docNode),
            $this->getExtractorForTypeDefTags($docNode, $currentClass),
            $this->getExtractorForTypeImportTags($docNode),
        );
    }

    private function makeCacheKey(Reflector $reflector): ?string
    {
        return match (true) {
            $reflector instanceof ReflectionClass
            => $reflector->getName(),

            $reflector instanceof ReflectionMethod
            => "{$reflector->getDeclaringClass()->getName()}->{$reflector->getName()}()",

            $reflector instanceof ReflectionFunction
            => "{$reflector->getName()}()",

            default
            => null,
        };
    }

    /**
     * @return iterable<string, TypeNode>
     */
    private function getExtractorForTemplateTags(PhpDocNode $docNode): iterable
    {
        /** @var list<PhpDocTagNode<TemplateTagValueNode>> $tags */
        $tags = array_merge(
            $docNode->getTagsByName('@template'),
            $docNode->getTagsByName('@phpstan-template'),
        );
        foreach ($tags as $tag) {
            yield $tag->value->name => new TemplateTypeNode(
                name: $tag->value->name,
                bound: $tag->value->bound
                    ? $this->getTypeResolver()->resolve($tag->value->bound, $this->getGenericsResolver())
                    : null,
            );
        }
    }

    /**
     * @param null|class-string $currentClass
     * @return iterable<string, TypeNode>
     */
    private function getExtractorForTypeDefTags(PhpDocNode $docNode, null|string $currentClass): iterable
    {
        /** @var list<PhpDocTagNode<TypeAliasTagValueNode>> $tags */
        $tags = $docNode->getTagsByName('@phpstan-type');
        if ($currentClass === null && count($tags)) {
            throw new RuntimeException('PHPStan local type requires a class');
        }
        foreach ($tags as $tag) {
            yield $tag->value->alias => new TypeDefTypeNode(
                name: $tag->value->alias,
                type: $this->getTypeResolver()->resolve($tag->value->type, $this->getGenericsResolver()),
                declaringClass: $currentClass,
            );
        }
    }

    /**
     * @return iterable<string, TypeNode>
     * @throws ReflectionException
     */
    private function getExtractorForTypeImportTags(PhpDocNode $docNode): iterable
    {
        /** @var list<PhpDocTagNode<TypeAliasImportTagValueNode>> $tags */
        $tags = $docNode->getTagsByName('@phpstan-import-type');
        foreach ($tags as $tag) {
            $name = $tag->value->importedAs ?? $tag->value->importedAlias;
            yield $name => new TypeDefTypeNode(
                name: $name,
                type: $this->getLocalTypeFromClass(
                    $this->getNodeClass($this->getTypeResolver()->resolve($tag->value->importedFrom, $this->getGenericsResolver())),
                    $tag->value->importedAlias,
                ),
                declaringClass: $tag->value->importedFrom->name,
            );
        }
    }

    /**
     * @param class-string $className
     * @throws ReflectionException
     */
    private function getLocalTypeFromClass(string $className, string $typeName): TypeNode
    {
        $block = $this->factory->createFromReflector(new ReflectionClass($className));
        /** @var list<TypeAliasTagValueNode> $tags */
        $tags = $block->getTags('@phpstan-type');
        foreach ($tags as $tag) {
            if ($tag->alias === $typeName) {
                return $this->getTypeResolver()->resolve($tag->type, $this->getGenericsResolver());
            }
        }
        throw new RuntimeException("A `@phpstan-type $typeName` PHPDoc tag was expected on class `$className`, but none was found");
    }

    private function getTypeResolver(): TypeResolver
    {
        return $this->typeResolver ?? throw new RuntimeException('No TypeResolver has been configured');
    }

    private function getGenericsResolver(): Resolver
    {
        return $this->genericsResolver ?? throw new RuntimeException('No GenericsResolver has been configured');
    }

    /**
     * @return class-string
     */
    private function getNodeClass(TypeNode $node): string
    {
        if (!$node instanceof IdentifierTypeNode) {
            throw new RuntimeException('PHPStan type import tag should point to an IdentifierTypeNode, got `' . get_debug_type($node) . '` instead');
        }

        if (!class_exists($node->name)
            && !interface_exists($node->name)
            && !trait_exists($node->name)
            && !enum_exists($node->name)
        ) {
            throw new RuntimeException("PHPStan type can only be imported from a simple class-like structure; symbol `$node->name` could not be found");
        }

        return $node->name;
    }
}
