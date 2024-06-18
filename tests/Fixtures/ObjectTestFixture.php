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
     * @param string|Stringable $name
     */
    public function greetPerson(string|Stringable $name): void
    {
        echo "$this->realProperty $name";
    }
}
