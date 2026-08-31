<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpDoc\Generics;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics\Extractor;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics\GenericTypeMap;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateTypeNode;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\TypeResolverTestFixture;

/**
 * @internal
 */
final class ExtractorTest extends TestCase
{
	public function testExtractFromClassNameWithInvalidClass(): void
	{
		$extractor = new Extractor(new Factory());

		$this->expectExceptionObject(new \InvalidArgumentException(
			'Class, interface, trait or enum does not exist: InvalidClass',
		));

		$extractor->extractFromClassName('InvalidClass');
	}

	public function testExtractImportTypeFromNonIdentifierThrows(): void
	{
		$block = Factory::createInstance()->createFromComment(
			comment: "/**\n * @phpstan-import-type Foo from T\n */",
			genericsResolver: new GenericTypeMap(
				templateTypesMap: ['T' => new TemplateTypeNode('T', null)],
			),
		);

		$this->expectExceptionObject(new \RuntimeException(
			'PHPStan type import tag should point to an IdentifierTypeNode, got `' . TemplateTypeNode::class . '` instead',
		));

		$block->getGenericsResolver();
	}

	public function testExtractImportTypeFromNonClassLikeThrows(): void
	{
		$block = Factory::createInstance()->createFromComment(
			comment: "/**\n * @phpstan-import-type Foo from int\n */",
		);

		$this->expectExceptionObject(new \RuntimeException(
			'PHPStan type can only be imported from a simple class-like structure; symbol `int` could not be found',
		));

		$block->getGenericsResolver();
	}

	public function testExtractImportTypeFromClassWithoutMatchingTypeThrows(): void
	{
		// The `from` target (`X`) resolves, via the injected generics map, to an existing class-like structure
		// (`TypeResolverTestFixture`) that only declares a `@phpstan-type TColors`, not the `Foo` being imported,
		// so there is nothing to import.
		$fromClass = TypeResolverTestFixture::class;
		$block = Factory::createInstance()->createFromComment(
			comment: "/**\n * @phpstan-import-type Foo from Bar\n */",
			genericsResolver: new GenericTypeMap(
				templateTypesMap: ['Bar' => new IdentifierTypeNode($fromClass)],
			),
		);

		$this->expectExceptionObject(new \RuntimeException(
			"A `@phpstan-type Foo` PHPDoc tag was expected on class `{$fromClass}`, but none was found",
		));

		$block->getGenericsResolver();
	}
}
