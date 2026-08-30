<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Represents a type definition (typically created with `@phpstan-type`).
 */
class TypeDefTypeNode implements TypeNode
{
	use NodeAttributes;

	/**
	 * @param string   $name           used to refer to the definition in the same class or other classes when imported
	 * @param TypeNode $type           the underlying type behind the definition
	 * @param string   $declaringClass the class where this type definition has been defined/declared
	 */
	public function __construct(
		public string $name,
		public TypeNode $type,
		public string $declaringClass,
	) {}

	/**
	 * @codeCoverageIgnore
	 */
	#[\Override]
	public function __toString(): string
	{
		return (string)$this->type;
	}
}
