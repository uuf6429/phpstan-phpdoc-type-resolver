<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * Returned by the {@see TypeResolver} when the original instance references incomplete types (types being either
 * template types, or themselves containing template types at any nesting level).
 */
class TemplateGenericTypeNode extends GenericTypeNode
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
