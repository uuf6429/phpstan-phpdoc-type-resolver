<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

class PhpImportsFile
{
    /**
     * @param list<PhpImportsBlock> $blocks
     */
    public function __construct(
        private readonly array $blocks,
    ) {}

    public function getNamespaceAt(?int $line): string
    {
        return $this->getBlockAt($line)->namespace ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function getImportsAt(?int $line): array
    {
        return $this->getBlockAt($line)->imports ?? [];
    }

    private function getBlockAt(?int $line): ?PhpImportsBlock
    {
        if($line === null) {
            return null;
        }

        foreach($this->blocks as $block) {
            if ($line < $block->startLine) {
                return null;
            }
            if ($line <= $block->endLine) {
                return $block;
            }
        }

        return null;
    }
}
