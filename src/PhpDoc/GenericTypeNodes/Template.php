<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericTypeNodes;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * Returned by the {@see TypeResolver} when the original instance references incomplete types (types being either
 * template types, or themselves containing template types at any nesting level).
 */
class Template extends GenericTypeNode
{
    //
}
