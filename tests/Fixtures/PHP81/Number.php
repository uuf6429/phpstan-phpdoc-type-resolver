<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\PHP81;

final class Number
{
	public function __construct(
		private readonly float|int $value,
	) {}

	public function asInteger(): int
	{
		return (int)$this->value;
	}

	public function asDecimal(): float
	{
		return (float)$this->value;
	}
}
