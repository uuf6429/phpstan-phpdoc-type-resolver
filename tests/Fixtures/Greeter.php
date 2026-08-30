<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

/**
 * @internal
 */
final class Greeter
{
	/**
	 * @param Person|object{name: string} $person
	 */
	public function greet($person): void
	{
		echo "Hello, {$person->name}!";
	}
}

/**
 * @internal
 */
final class Person
{
	public string $name;
}
