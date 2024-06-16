<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
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
    private static function reflectFunction($function): ReflectionFunction
    {
        return new ReflectionFunction($function);
    }

    /**
     * @throws ReflectionException
     */
    private static function reflectCallable(callable $callable): ReflectionFunctionAbstract
    {
        switch (true) {
            case is_array($callable):
                return self::reflectMethod(...$callable);

            case $callable instanceof Closure:
            case is_string($callable):
                return self::reflectFunction($callable);

            default:
                throw new InvalidArgumentException('Unsupported callable format: ' . get_debug_type($callable));
        }
    }
}
