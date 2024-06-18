<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

trait ReflectsValuesTrait
{
    /**
     * @param array{0: class-string, 1: string} $call
     * @throws ReflectionException
     */
    private static function reflectMethod(array $call): ReflectionMethod
    {
        return new ReflectionMethod($call[0], $call[1]);
    }

    /**
     * @param callable-string|Closure $function
     * @throws ReflectionException
     */
    private static function reflectFunction(string|Closure $function): ReflectionFunction
    {
        return new ReflectionFunction($function);
    }
}
