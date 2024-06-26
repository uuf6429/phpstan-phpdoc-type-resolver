<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Reflector;

class ReflectorScopeResolver
{
    public function resolve(Reflector $reflector): TypeScope
    {
        /**
         * @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection PHPUnit is not able to handle coverage for match expressions correctly.
         */
        switch (true) {
            case $reflector instanceof ReflectionClass:
                return new TypeScope(
                    file: $reflector->getFileName() ?: null,
                    line: $reflector->getStartLine() ?: null,
                    class: $reflector->getName(),
                    comment: $reflector->getDocComment() ?: '',
                );

            case $reflector instanceof ReflectionMethod:
                return new TypeScope(
                    file: $reflector->getFileName() ?: null,
                    line: $reflector->getStartLine() ?: null,
                    class: $reflector->getDeclaringClass()->getName(),
                    comment: $reflector->getDocComment() ?: '',
                );

            case $reflector instanceof ReflectionFunction:
                return new TypeScope(
                    file: $reflector->getFileName() ?: null,
                    line: $reflector->getStartLine() ?: null,
                    class: ($class = $reflector->getClosureScopeClass()) ? $class->getName() : null,
                    comment: $reflector->getDocComment() ?: '',
                );

            default:
                throw new InvalidArgumentException('Cannot determine scope information for reflector of type ' . get_class($reflector));
        }
    }
}
