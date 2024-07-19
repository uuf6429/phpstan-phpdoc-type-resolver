<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpImports;

/**
 * Information about a namespaced block of code, including:
 * - startLine/endLine line number of where this block started and ended.
 * - namespace of the block.
 * - any imported/aliased symbols used in the block.
 */
class Block
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
