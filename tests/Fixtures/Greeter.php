<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

/**
 * @internal
 */
final class Greeter
{
	/**
	 * @param object{name: string}|Person $person
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
