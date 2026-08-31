<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpDoc\Types;

use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateTypeNode;

/**
 * @internal
 */
final class TemplateTypeNodeTest extends TestCase
{
	public function testBoundStringification(): void
	{
		$node = new TemplateTypeNode(
			name: 'TType',
			bound: new ArrayShapeNode([]),
		);

		$actualString = (string)$node;

		$this->assertSame('TType of array{}', $actualString);
	}

	public function testUnboundStringification(): void
	{
		$node = new TemplateTypeNode(
			name: 'TType',
			bound: null,
		);

		$actualString = (string)$node;

		$this->assertSame('TType', $actualString);
	}
}
