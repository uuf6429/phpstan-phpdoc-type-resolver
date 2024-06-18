<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

class PhpImportsBlock
{
    /**
     * @param array<string, string> $imports
     */
    public function __construct(
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly string $namespace,
        public readonly array $imports,
    ) {}
}
