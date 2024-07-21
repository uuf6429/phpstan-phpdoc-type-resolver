<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

class ResolverRefState implements ResolverStateInterface
{
    /**
     * @param list<ResolverStateInterface> $states
     */
    public function __construct(
        private readonly array $states,
    ) {
        //
    }

    public function isConcrete(): bool
    {
        foreach ($this->states as $state) {
            if (!$state->isConcrete()) {
                return false;
            }
        }
        return true;
    }

    public function setConcrete(bool $enabled): void
    {
        foreach ($this->states as $state) {
            $state->setConcrete($enabled);
        }
    }
}
