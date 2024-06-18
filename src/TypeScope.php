<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

class TypeScope
{
    /**
     * Represents the scope where the type occurred:
     * The file/line(approximate) needed for handling namespacing correctly (especially files with multiple namespaces)
     * The class (must fully-qualified!) is for resolving $this, self etc.
     * @param null|class-string $class
     */
    public function __construct(
        public readonly ?string $file,
        public readonly ?int $line,
        public readonly ?string $class,
        public readonly string $comment,
    ) {}
}
