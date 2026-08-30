<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics;

/**
 * A simple mutable boolean flag. It carries no meaning of its own; the meaning is given by the property that holds it
 * (much like the number in {@code $obj->id = 123} is just a number that becomes an id by virtue of the property name).
 *
 * @internal
 */
interface Flag
{
	public function isSet(): bool;

	public function set(bool $value): void;
}
