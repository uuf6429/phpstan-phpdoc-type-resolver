<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

class Scope
{
    /**
     * PHPDoc Scope Representation
     *
     * Represents the scope where a PHPDoc comment occurred, together with some relevant information:
     * - File & (approximate)line - for handling namespacing correctly (especially files with multiple namespaces).
     * - Class - (must fully-qualified!) is for resolving $this, self etc.
     * - Comment - the PHPDoc comment block.
     *
     * @param null|class-string $class
     */
    public function __construct(
        public readonly ?string $file,
        public readonly ?int $line,
        public readonly ?string $class,
        public readonly string $comment,
    ) {
        //
    }
}
