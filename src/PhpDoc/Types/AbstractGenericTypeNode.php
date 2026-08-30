<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * Base class for the generic type nodes produced by the {@see TypeResolver}. It carries the resolved template types
 * alongside the standard {@see GenericTypeNode} data; the concrete subclasses ({@see TemplateGenericTypeNode} and
 * {@see ConcreteGenericTypeNode}) exist purely as `instanceof` markers distinguishing whether the generic still
 * contains unresolved template types.
 */
abstract class AbstractGenericTypeNode extends GenericTypeNode
{
	/**
	 * @param list<TypeNode>         $templateTypes
	 * @param list<TypeNode>         $genericTypes
	 * @param list<self::VARIANCE_*> $variances
	 */
	public function __construct(
		IdentifierTypeNode $type,
		/** @readonly */
		public array $templateTypes,
		array $genericTypes,
		array $variances = [],
	) {
		parent::__construct($type, $genericTypes, $variances);
	}
}
