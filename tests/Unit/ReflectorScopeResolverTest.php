<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use Attribute;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\ReflectorScopeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\AttributeTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\IntegerEnumTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\ObjectTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\PlainEnumTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\StringEnumTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\ReflectsValuesTrait;

use function uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\getFunctionWithParameter;

class ReflectorScopeResolverTest extends TestCase
{
    use ReflectsValuesTrait;

    #[DataProvider('reflectorScopeResolverDataProvider')]
    public function testReflectorScopeResolver(?Scope $expectedResult, ?Exception $expectedException, Reflector $reflector): void
    {
        $resolver = new ReflectorScopeResolver();

        if ($expectedException) {
            $this->expectException(get_class($expectedException));
            $this->expectExceptionMessage($expectedException->getMessage());
        }

        $actualResult = (array)$resolver->resolve($reflector);
        if (isset($actualResult['file'])) {
            $actualResult['file'] = str_replace(DIRECTORY_SEPARATOR, '/', $actualResult['file']);
        }
        $expectedResult = (array)$expectedResult;
        if (isset($expectedResult['file'])) {
            $expectedResult['file'] = str_replace(DIRECTORY_SEPARATOR, '/', $expectedResult['file']);
        }

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return iterable<string, array{expectedResult: ?Scope, expectedException: ?Exception, reflector: Reflector}>
     * @throws ReflectionException
     */
    public static function reflectorScopeResolverDataProvider(): iterable
    {
        yield 'ReflectionProperty' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/ObjectTestFixture.php',
                line: 12,
                class: ObjectTestFixture::class,
                comment:  <<<'PHP'
                    /**
                         * @var 'hello'|'bye'
                         */
                    PHP,
                inheritedGenericTypes: [
                    'TColors',
                    'TOtherColors',
                ],
            ),
            'expectedException' => null,
            'reflector' => new ReflectionProperty(ObjectTestFixture::class, 'realProperty'),
        ];

        yield 'ReflectionParameter' => [
            'expectedResult' => null,
            'expectedException' => new InvalidArgumentException(
                'Cannot determine scope information for reflector of type ReflectionParameter',
            ),
            'reflector' => new ReflectionParameter(getFunctionWithParameter(), 'greeting'),
        ];

        yield 'ReflectionClass' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/ObjectTestFixture.php',
                line: 12,
                class: ObjectTestFixture::class,
                comment:  <<<'PHP'
                    /**
                     * @property string $dynamicProperty
                     * @phpstan-import-type TColors from TypeResolverTestFixture
                     * @phpstan-import-type TColors from TypeResolverTestFixture as TOtherColors
                     */
                    PHP,
                inheritedGenericTypes: [],
            ),
            'expectedException' => null,
            'reflector' => new ReflectionClass(ObjectTestFixture::class),
        ];

        yield 'ReflectionObject' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/ObjectTestFixture.php',
                line: 12,
                class: ObjectTestFixture::class,
                comment:  <<<'PHP'
                    /**
                     * @property string $dynamicProperty
                     * @phpstan-import-type TColors from TypeResolverTestFixture
                     * @phpstan-import-type TColors from TypeResolverTestFixture as TOtherColors
                     */
                    PHP,
                inheritedGenericTypes: [],
            ),
            'expectedException' => null,
            'reflector' => new ReflectionObject(new ObjectTestFixture('hello')),
        ];

        yield 'ReflectionEnum' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/IntegerEnumTestFixture.php',
                line: 5,
                class: IntegerEnumTestFixture::class,
                comment: '',
                inheritedGenericTypes: [],
            ),
            'expectedException' => null,
            'reflector' => new ReflectionEnum(IntegerEnumTestFixture::class),
        ];

        yield 'ReflectionEnumUnitCase' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/PlainEnumTestFixture.php',
                line: 5,
                class: PlainEnumTestFixture::class,
                comment: '',
                inheritedGenericTypes: [],
            ),
            'expectedException' => null,
            'reflector' => (new ReflectionEnum(PlainEnumTestFixture::class))->getCase('Case1'),
        ];

        yield 'ReflectionEnumBackedCase' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/StringEnumTestFixture.php',
                line: 5,
                class: StringEnumTestFixture::class,
                comment: '',
                inheritedGenericTypes: [],
            ),
            'expectedException' => null,
            'reflector' => (new ReflectionEnum(StringEnumTestFixture::class))->getCase('Case1'),
        ];
        /*
        yield 'ReflectionExtension' => [
            'expectedResult' => null,
            'expectedException' => null,
            'reflector' => self::reflectMethod(),
        ];

        yield 'ReflectionZendExtension' => [
            'expectedResult' => null,
            'expectedException' => null,
            'reflector' => self::reflectMethod(),
        ];
        */
        yield 'ReflectionClassConstant' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/ObjectTestFixture.php',
                line: 12,
                class: ObjectTestFixture::class,
                comment: '',
                inheritedGenericTypes: [
                    'TColors',
                    'TOtherColors',
                ],
            ),
            'expectedException' => null,
            'reflector' => new ReflectionClassConstant(ObjectTestFixture::class, 'TEST'),
        ];

        yield 'ReflectionMethod' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/ObjectTestFixture.php',
                line: 37,
                class: ObjectTestFixture::class,
                comment:  <<<'PHP'
                    /**
                         * Greeter
                         *
                         * A function that greets the entity given their name with the desired greeting.
                         * For example, one could greet the world with `(new ObjectTestFixture('Hello'))->greet('World')`.
                         *
                         * @param string|Stringable $name
                         */
                    PHP,
                inheritedGenericTypes: [
                    'TColors',
                    'TOtherColors',
                ],
            ),
            'expectedException' => null,
            'reflector' => self::reflectMethod([ObjectTestFixture::class, 'greet']),
        ];

        yield 'ReflectionFunction' => [
            'expectedResult' => new Scope(
                file: dirname(__DIR__) . '/Fixtures/functions.php',
                line: 47,
                class: null,
                comment:  <<<'PHP'
                    /**
                     * @param 'hello'|'bye' $greeting
                     */
                    PHP,
                inheritedGenericTypes: [],
            ),
            'expectedException' => null,
            'reflector' => self::reflectFunction(getFunctionWithParameter()),
        ];

        yield 'ReflectionAttribute' => [
            'expectedResult' => null,
            'expectedException' => new InvalidArgumentException(
                'Cannot determine scope information for reflector of type ReflectionAttribute',
            ),
            'reflector' => (new ReflectionClass(AttributeTestFixture::class))
                ->getAttributes(Attribute::class)[0],
        ];
    }
}
