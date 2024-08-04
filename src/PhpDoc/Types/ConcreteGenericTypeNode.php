<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * The counterpart of {@see TemplateGenericTypeNode} returned by the {@see TypeResolver}, when all generic types (templates) have been
 * resolve to an actual existing class/type that is also itself concrete.
 */
class ConcreteGenericTypeNode extends GenericTypeNode
{
    /**
     * @param TypeNode[] $templateTypes
     * @param TypeNode[] $genericTypes
     * @param (self::VARIANCE_*)[] $variances
     */
    public function __construct(IdentifierTypeNode $type, public array $templateTypes, array $genericTypes, array $variances = [])
    {
        parent::__construct($type, $genericTypes, $variances);
    }
}
