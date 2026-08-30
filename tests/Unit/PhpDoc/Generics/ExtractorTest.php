<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpDoc\Generics;

use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics\Extractor;

class ExtractorTest extends TestCase
{
	public function testExtractFromClassNameWithInvalidClass(): void
	{
		$extractor = new Extractor(new Factory());

		$this->expectExceptionObject(new \InvalidArgumentException(
			'Class, interface, trait or enum does not exist: InvalidClass',
		));

		$extractor->extractFromClassName('InvalidClass');
	}
}
