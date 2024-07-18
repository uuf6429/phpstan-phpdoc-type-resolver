<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Reflector;

class ReflectorScopeResolver
{
    public function resolve(Reflector $reflector): Scope
    {
        return match (true) {
            $reflector instanceof ReflectionClass
            => new Scope(
                file: $reflector->getFileName() ?: null,
                line: $reflector->getStartLine() ?: null,
                class: $reflector->getName(),
                comment: $reflector->getDocComment() ?: '',
            ),

            $reflector instanceof ReflectionMethod
            => new Scope(
                file: $reflector->getFileName() ?: null,
                line: $reflector->getStartLine() ?: null,
                class: $reflector->getDeclaringClass()->getName(),
                comment: $reflector->getDocComment() ?: '',
            ),

            $reflector instanceof ReflectionFunction
            => new Scope(
                file: $reflector->getFileName() ?: null,
                line: $reflector->getStartLine() ?: null,
                class: ($class = $reflector->getClosureScopeClass()) ? $class->getName() : null,
                comment: $reflector->getDocComment() ?: '',
            ),

            default
            => throw new InvalidArgumentException('Cannot determine scope information for reflector of type ' . get_class($reflector)),
        };
    }
}
