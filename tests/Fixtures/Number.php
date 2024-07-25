<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

final class Number
{
    public function __construct(
        private readonly int|float $value,
    ) {
        //
    }

    public function asInteger(): int
    {
        return (int)$this->value;
    }

    public function asDecimal(): float
    {
        return (float)$this->value;
    }
}
