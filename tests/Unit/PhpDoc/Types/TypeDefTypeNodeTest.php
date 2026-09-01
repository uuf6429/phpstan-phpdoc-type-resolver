<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TypeDefTypeNode;

/**
 * @internal
 */
final class TypeDefTypeNodeTest extends TestCase
{
	public function testStringification(): void
	{
		$node = new TypeDefTypeNode(
			name: 'TType',
			type: ArrayShapeNode::createSealed([]),
			declaringClass: 'SomeClass',
		);

		$actualString = (string)$node;

		$this->assertSame('array{}', $actualString);
	}
}
