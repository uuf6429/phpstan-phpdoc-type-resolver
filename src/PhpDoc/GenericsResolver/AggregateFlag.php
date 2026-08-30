<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

/**
 * @internal
 */
final class AggregateFlag implements Flag
{
	/**
	 * @param list<Flag> $flags
	 */
	public function __construct(
		/** @readonly */
		private array $flags,
	) {}

	#[\Override]
	public function isSet(): bool
	{
		foreach ($this->flags as $flag) {
			if (!$flag->isSet()) {
				return false;
			}
		}

		return true;
	}

	#[\Override]
	public function set(bool $value): void
	{
		foreach ($this->flags as $flag) {
			$flag->set($value);
		}
	}
}
