<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * The counterpart of {@see TemplateGenericTypeNode} returned by the {@see TypeResolver}, when all generic types (templates) have been
 * resolve to an actual existing class/type that is also itself concrete.
 */
class ConcreteGenericTypeNode extends GenericTypeNode
{
    //
}
