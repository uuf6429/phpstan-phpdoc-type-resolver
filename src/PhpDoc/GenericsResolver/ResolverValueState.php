<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

class ResolverValueState implements ResolverStateInterface
{
    public function __construct(
        private bool $isConcrete,
    ) {
        //
    }

    public function isConcrete(): bool
    {
        return $this->isConcrete;
    }

    public function setConcrete(bool $enabled): void
    {
        $this->isConcrete = $enabled;
    }
}
