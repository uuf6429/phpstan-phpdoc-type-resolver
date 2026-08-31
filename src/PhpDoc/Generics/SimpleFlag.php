<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics;

/**
 * @internal
 */
final class SimpleFlag implements Flag
{
	public function __construct(
		private bool $value,
	) {}

	#[\Override]
	public function isSet(): bool
	{
		return $this->value;
	}

	#[\Override]
	public function set(bool $value): void
	{
		$this->value = $value;
	}
}
