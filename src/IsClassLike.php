<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver;

/**
 * @internal
 */
trait IsClassLike
{
	/**
	 * @phpstan-assert-if-true class-string $symbol
	 */
	private function isClassLike(string $symbol): bool
	{
		return class_exists($symbol) || trait_exists($symbol) || interface_exists($symbol) || enum_exists($symbol);
	}
}
