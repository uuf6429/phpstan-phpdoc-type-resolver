<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests;

/**
 * @internal
 */
trait ReflectsValuesTrait
{
	/**
	 * @param array{object|string, string}|(\Closure(mixed ...): mixed) $callable
	 *
	 * @return ($callable is array ? \ReflectionMethod : \ReflectionFunction)
	 *
	 * @throws \ReflectionException
	 */
	private static function reflectCallable(array|\Closure|string $callable): \ReflectionFunction|\ReflectionMethod
	{
		return \is_array($callable)
			? new \ReflectionMethod($callable[0], $callable[1])
			: new \ReflectionFunction($callable);
	}
}
