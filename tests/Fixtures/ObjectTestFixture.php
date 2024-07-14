<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

use Stringable;

/**
 * @property string $dynamicProperty
 */
class ObjectTestFixture
{
    public const TEST = 123;

    /**
     * @var 'hello'|'bye'
     */
    public readonly string $realProperty;

    /**
     * @param 'hello'|'bye' $greeting
     */
    public function __construct(string $greeting)
    {
        $this->realProperty = $greeting;
    }

    /**
     * Greeter
     *
     * A function that greets the entity given their name with the desired greeting.
     * For example, one could greet the world with `(new ObjectTestFixture('Hello'))->greet('World')`.
     *
     * @param string|Stringable $name
     */
    public function greet(string|Stringable $name): void
    {
        echo "$this->realProperty $name";
    }
}
