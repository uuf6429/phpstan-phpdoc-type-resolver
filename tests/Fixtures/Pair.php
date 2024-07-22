<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

/**
 * @template TLeft of mixed
 * @template TRight of mixed
 */
abstract class Pair
{
    /**
     * @var TLeft
     */
    protected mixed $left;

    /**
     * @var TRight
     */
    protected mixed $right;

    /**
     * @return Pair<int, string>
     */
    abstract public static function makeArrayString(int $index, string $value): self;

    /**
     * @phpstan-ignore-next-line
     * @template TValue of mixed
     * @return Pair<int, TValue>
     */
    abstract public static function makeArrayValue(int $index, mixed $value): self;

    /**
     * @template TTwinType of mixed
     * @param TTwinType $left
     * @param TTwinType $right
     * @return Pair<TTwinType, TTwinType>
     */
    abstract public static function makeTwins(mixed $left, mixed $right): self;
}
