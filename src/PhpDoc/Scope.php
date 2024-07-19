<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

class Scope
{
    /**
     * PHPDoc Scope
     *
     * Represents a collection of information at the location where a PHPDoc comment occurred:
     * - File & (approximate)line - for handling namespacing correctly (especially files with multiple namespaces).
     * - Class - (must fully-qualified!) is for resolving $this, self etc.
     * - Comment - the PHPDoc comment block.
     * - Inherited Generic Types - given that the scope, for example, represents a method, this is a list of generics
     *   from the class-level PHPDoc, if any (and not generic types from the current $comment).
     *
     * @param null|class-string $class
     * @param list<string> $inheritedGenericTypes
     */
    public function __construct(
        public readonly ?string $file,
        public readonly ?int $line,
        public readonly ?string $class,
        public readonly string $comment,
        public readonly array $inheritedGenericTypes,
    ) {
        //
    }
}
