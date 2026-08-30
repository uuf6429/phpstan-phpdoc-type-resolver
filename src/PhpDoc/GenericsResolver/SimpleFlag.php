<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

/**
 * @internal
 */
class SimpleFlag implements Flag
{
	public function __construct(
		private bool $value,
	) {
		//
	}

	public function isSet(): bool
	{
		return $this->value;
	}

	public function set(bool $value): void
	{
		$this->value = $value;
	}
}
