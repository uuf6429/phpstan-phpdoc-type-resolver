<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Factory as GenericsResolverFactory;

class ReflectorScopeResolver
{
    public function __construct(
        private readonly GenericsResolverFactory $genericsResolverFactory,
    ) {
        //
    }

    public function resolve(Reflector $reflector): Scope
    {
        return match (true) {
            $reflector instanceof ReflectionClass
            => new Scope(
                file: $reflector->getFileName() ?: null,
                line: $reflector->getStartLine() ?: null,
                class: $reflector->getName(),
                comment: $reflector->getDocComment() ?: '',
                genericsResolver: new GenericsResolver\Resolver(),
            ),

            $reflector instanceof ReflectionMethod && ($class = $reflector->getDeclaringClass())
            => new Scope(
                file: $reflector->getFileName() ?: null,
                line: $reflector->getStartLine() ?: null,
                class: $class->getName(),
                comment: $reflector->getDocComment() ?: '',
                genericsResolver: $this->genericsResolverFactory->extractFromReflector($class),
            ),

            $reflector instanceof ReflectionFunction
            => new Scope(
                file: $reflector->getFileName() ?: null,
                line: $reflector->getStartLine() ?: null,
                class: ($class = $reflector->getClosureScopeClass()) ? $class->getName() : null,
                comment: $reflector->getDocComment() ?: '',
                genericsResolver: new GenericsResolver\Resolver(),
            ),

            $reflector instanceof ReflectionClassConstant && ($class = $reflector->getDeclaringClass())
            => new Scope(
                file: $class->getFileName() ?: null,
                line: $class->getStartLine() ?: null,
                class: $class->getName(),
                comment: $reflector->getDocComment() ?: '',
                genericsResolver: $this->genericsResolverFactory->extractFromReflector($class),
            ),

            $reflector instanceof ReflectionProperty && ($class = $reflector->getDeclaringClass())
            => new Scope(
                file: $class->getFileName() ?: null,
                line: $class->getStartLine() ?: null,
                class: $class->getName(),
                comment: $reflector->getDocComment() ?: '',
                genericsResolver: $this->genericsResolverFactory->extractFromReflector($class),
            ),

            default
            => throw new InvalidArgumentException('Cannot determine scope information for reflector of type ' . get_class($reflector)),
        };
    }
}
