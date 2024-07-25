<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Represents an declaration for a generic/template type (typically created with `@template`).
 */
class TemplateTypeNode implements TypeNode
{
    use NodeAttributes;

    /**
     * @param string $name The name of the type e.g. `T` in `@template T`.
     * @param null|TypeNode $bound A type the template is limited to e.g. `object` in case of `@template T of object`.
     */
    public function __construct(
        public string $name,
        public null|TypeNode $bound,
    ) {
        //
    }

    public function __toString(): string
    {
        return $this->bound ? "$this->name of $this->bound" : $this->name;
    }
}
