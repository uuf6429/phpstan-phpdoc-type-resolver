<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use RuntimeException;

class VirtualTypeNode implements TypeNode
{
    use NodeAttributes;

    public function __construct(
        public string $name,
        public TypeNode $type,
        public string $declaringClass,
    ) {
        //
    }

    public function __toString(): string
    {
        throw new RuntimeException('Converting a VirtualTypeNode to a string is not supported at the moment');
    }
}
