<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type;
use PHPUnit\Framework\TestCase;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory as PhpDocFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\AggregateFlag;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Extractor as GenericsExtractor;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\GenericTypeMap;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\SimpleFlag;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\ReflectorScopeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TypeDefTypeNode;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\AttributeTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\ObjectTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\PHP81\IntegerEnum;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\PHP81\PlainEnum;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\PHP81\StringEnum;
use uuf6429\PHPStanPHPDocTypeResolverTests\ReflectsValuesTrait;

use function uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\getFunctionWithParameter;

/**
 * @internal
 *
 * @coversNothing
 */
final class ReflectorScopeResolverTest extends TestCase
{
	use ReflectsValuesTrait;

	/**
	 * @dataProvider provideReflectorScopeResolverCases
	 */
	public function testReflectorScopeResolver(
		?Scope $expectedResult,
		?\Exception $expectedException,
		\ReflectionAttribute|\Reflector|null $reflector,
		?string $minPhpVersion = null,
	): void {
		if (null !== $minPhpVersion && version_compare($minPhpVersion, PHP_VERSION, '>=')) {
			self::markTestSkipped("PHP {$minPhpVersion} required, but current PHP version is " . PHP_VERSION);
		}
		if (null === $reflector) {
			self::markTestSkipped('Reflector could not be constructed');
		}

		$resolver = new ReflectorScopeResolver(new GenericsExtractor(new PhpDocFactory()));

		if (null !== $expectedException) {
			$this->expectException(\get_class($expectedException));
			$this->expectExceptionMessage($expectedException->getMessage());
		}

		$actualResult = $resolver->resolve($reflector);

		self::assertSame(
			null === $expectedResult ? null : [
				'file' => $expectedResult->file,
				'line' => $expectedResult->line,
				'class' => $expectedResult->class,
				'comment' => $expectedResult->comment,
			],
			[
				'file' => $actualResult->file,
				'line' => $actualResult->line,
				'class' => $actualResult->class,
				'comment' => $actualResult->comment,
			],
		);
	}

	/**
	 * @return iterable<string, array{expectedResult: ?Scope, expectedException: ?\Exception, reflector: ?\Reflector, minPhpVersion?: string}>
	 *
	 * @throws \ReflectionException
	 */
	public static function provideReflectorScopeResolverCases(): iterable
	{
		$importedTypesMap = [
			'TColors' => new TypeDefTypeNode(
				name: 'TColors',
				type: new Type\ArrayShapeNode(
					items: [
						new Type\ArrayShapeItemNode(
							keyName: new Type\IdentifierTypeNode('red'),
							optional: false,
							valueType: new Type\ConstTypeNode(constExpr: new ConstExprStringNode('#F00')),
						),
						new Type\ArrayShapeItemNode(
							keyName: new Type\IdentifierTypeNode('green'),
							optional: false,
							valueType: new Type\ConstTypeNode(constExpr: new ConstExprStringNode('#0F0')),
						),
						new Type\ArrayShapeItemNode(
							keyName: new Type\IdentifierTypeNode('blue'),
							optional: false,
							valueType: new Type\ConstTypeNode(constExpr: new ConstExprStringNode('#00F')),
						),
					],
					sealed: true,
					kind: 'array',
				),
				declaringClass: 'TypeResolverTestFixture',
			),
			'TOtherColors' => new TypeDefTypeNode(
				name: 'TOtherColors',
				type: new Type\ArrayShapeNode(
					items: [
						new Type\ArrayShapeItemNode(
							keyName: new Type\IdentifierTypeNode('red'),
							optional: false,
							valueType: new Type\ConstTypeNode(constExpr: new ConstExprStringNode('#F00')),
						),
						new Type\ArrayShapeItemNode(
							keyName: new Type\IdentifierTypeNode('green'),
							optional: false,
							valueType: new Type\ConstTypeNode(constExpr: new ConstExprStringNode('#0F0')),
						),
						new Type\ArrayShapeItemNode(
							keyName: new Type\IdentifierTypeNode('blue'),
							optional: false,
							valueType: new Type\ConstTypeNode(constExpr: new ConstExprStringNode('#00F')),
						),
					],
					sealed: true,
					kind: 'array',
				),
				declaringClass: 'TypeResolverTestFixture',
			),
		];

		yield 'ReflectionProperty' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'ObjectTestFixture.php',
				line: 13,
				class: ObjectTestFixture::class,
				comment: <<<'PHP'
					/**
						 * @var 'bye'|'hello'
						 */
					PHP,
				genericsResolver: new GenericTypeMap(
					importedTypesMap: $importedTypesMap,
					concreteness: new AggregateFlag([
						new SimpleFlag(true),
						new SimpleFlag(true),
					]),
				),
			),
			'expectedException' => null,
			'reflector' => new \ReflectionProperty(ObjectTestFixture::class, 'realProperty'),
		];

		yield 'ReflectionParameter' => [
			'expectedResult' => null,
			'expectedException' => new \InvalidArgumentException(
				'Cannot determine scope information for reflector of type ReflectionParameter',
			),
			'reflector' => new \ReflectionParameter(getFunctionWithParameter(), 'greeting'),
		];

		yield 'ReflectionClass' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'ObjectTestFixture.php',
				line: 13,
				class: ObjectTestFixture::class,
				comment: <<<'PHP'
					/**
					 * @property string $dynamicProperty
					 *
					 * @phpstan-import-type TColors from TypeResolverTestFixture
					 * @phpstan-import-type TColors from TypeResolverTestFixture as TOtherColors
					 */
					PHP,
				genericsResolver: new GenericTypeMap(),
			),
			'expectedException' => null,
			'reflector' => new \ReflectionClass(ObjectTestFixture::class),
		];

		yield 'ReflectionObject' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'ObjectTestFixture.php',
				line: 13,
				class: ObjectTestFixture::class,
				comment: <<<'PHP'
					/**
					 * @property string $dynamicProperty
					 *
					 * @phpstan-import-type TColors from TypeResolverTestFixture
					 * @phpstan-import-type TColors from TypeResolverTestFixture as TOtherColors
					 */
					PHP,
				genericsResolver: new GenericTypeMap(),
			),
			'expectedException' => null,
			'reflector' => new \ReflectionObject(new ObjectTestFixture('hello')),
		];

		yield 'ReflectionEnum' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'PHP81' . \DIRECTORY_SEPARATOR . 'IntegerEnum.php',
				line: 7,
				class: IntegerEnum::class,
				comment: '',
				genericsResolver: new GenericTypeMap(),
			),
			'expectedException' => null,
			'reflector' => class_exists(\ReflectionEnum::class) ? new \ReflectionEnum(IntegerEnum::class) : null,
			'minPhpVersion' => '8.1',
		];

		yield 'ReflectionEnumUnitCase' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'PHP81' . \DIRECTORY_SEPARATOR . 'PlainEnum.php',
				line: 7,
				class: PlainEnum::class,
				comment: '',
				genericsResolver: new GenericTypeMap(
					concreteness: new AggregateFlag([
						new SimpleFlag(true),
						new SimpleFlag(true),
					]),
				),
			),
			'expectedException' => null,
			'reflector' => class_exists(\ReflectionEnum::class) ? (new \ReflectionEnum(PlainEnum::class))->getCase('Case1') : null,
			'minPhpVersion' => '8.1',
		];

		yield 'ReflectionEnumBackedCase' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'PHP81' . \DIRECTORY_SEPARATOR . 'StringEnum.php',
				line: 7,
				class: StringEnum::class,
				comment: '',
				genericsResolver: new GenericTypeMap(
					concreteness: new AggregateFlag([
						new SimpleFlag(true),
						new SimpleFlag(true),
					]),
				),
			),
			'expectedException' => null,
			'reflector' => class_exists(\ReflectionEnum::class) ? (new \ReflectionEnum(StringEnum::class))->getCase('Case1') : null,
			'minPhpVersion' => '8.1',
		];

		/*
		yield 'ReflectionExtension' => [
			'expectedResult' => null,
			'expectedException' => null,
			'reflector' => self::reflectCallable(),
		];

		yield 'ReflectionZendExtension' => [
			'expectedResult' => null,
			'expectedException' => null,
			'reflector' => self::reflectCallable(),
		];
		*/
		yield 'ReflectionClassConstant' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'ObjectTestFixture.php',
				line: 13,
				class: ObjectTestFixture::class,
				comment: '',
				genericsResolver: new GenericTypeMap(
					importedTypesMap: $importedTypesMap,
					concreteness: new AggregateFlag([
						new SimpleFlag(true),
						new SimpleFlag(true),
					]),
				),
			),
			'expectedException' => null,
			'reflector' => new \ReflectionClassConstant(ObjectTestFixture::class, 'TEST'),
		];

		yield 'ReflectionMethod' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'ObjectTestFixture.php',
				line: 38,
				class: ObjectTestFixture::class,
				comment: <<<'PHP'
					/**
						 * Greeter.
						 *
						 * A function that greets the entity given their name with the desired greeting.
						 * For example, one could greet the world with `(new ObjectTestFixture('Hello'))->greet('World')`.
						 *
						 * @param string|\Stringable $name
						 */
					PHP,
				genericsResolver: new GenericTypeMap(
					importedTypesMap: $importedTypesMap,
					concreteness: new AggregateFlag([
						new SimpleFlag(true),
						new SimpleFlag(true),
					]),
				),
			),
			'expectedException' => null,
			'reflector' => self::reflectCallable([ObjectTestFixture::class, 'greet']),
		];

		yield 'ReflectionFunction' => [
			'expectedResult' => new Scope(
				file: \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR . 'functions.php',
				line: 54,
				class: null,
				comment: <<<'PHP'
					/**
					 * @param 'bye'|'hello' $greeting
					 */
					PHP,
				genericsResolver: new GenericTypeMap(),
			),
			'expectedException' => null,
			'reflector' => self::reflectCallable(getFunctionWithParameter()),
		];

		yield 'ReflectionAttribute' => [
			'expectedResult' => null,
			'expectedException' => new \InvalidArgumentException(
				'Cannot determine scope information for reflector of type ReflectionAttribute',
			),
			'reflector' => (new \ReflectionClass(AttributeTestFixture::class))
				->getAttributes(\Attribute::class)[0]
				?? throw new \RuntimeException('Cannot get attribute from fixture'),
		];
	}
}
