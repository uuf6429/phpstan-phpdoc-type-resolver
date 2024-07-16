<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use Attribute;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\ReflectorScopeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\AttributeTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\ObjectTestFixture;
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
            $actualResult['file'] = 'basename://' . basename($actualResult['file']);
        }

        $this->assertEquals((array)$expectedResult, $actualResult);
    }

    /**
     * @return iterable<string, array{expectedResult: ?Scope, expectedException: ?Exception, reflector: Reflector}>
     * @throws ReflectionException
     */
    public static function reflectorScopeResolverDataProvider(): iterable
    {
        yield 'ReflectionProperty' => [
            'expectedResult' => null,
            'expectedException' => new InvalidArgumentException(
                'Cannot determine scope information for reflector of type ReflectionProperty',
            ),
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
                file: 'basename://ObjectTestFixture.php',
                line: 10,
                class: ObjectTestFixture::class,
                comment:  <<<'PHP'
                    /**
                     * @property string $dynamicProperty
                     */
                    PHP,
            ),
            'expectedException' => null,
            'reflector' => new ReflectionClass(ObjectTestFixture::class),
        ];

        yield 'ReflectionObject' => [
            'expectedResult' => new Scope(
                file: 'basename://ObjectTestFixture.php',
                line: 10,
                class: ObjectTestFixture::class,
                comment:  <<<'PHP'
                    /**
                     * @property string $dynamicProperty
                     */
                    PHP,
            ),
            'expectedException' => null,
            'reflector' => new ReflectionObject(new ObjectTestFixture('hello')),
        ];
        /* TODO
        yield 'ReflectionEnum' => [
            'expectedResult' => null,
            'expectedException' => null,
            'reflector' => self::reflectMethod(),
        ];

        yield 'ReflectionEnumUnitCase' => [
            'expectedResult' => null,
            'expectedException' => null,
            'reflector' => new \ReflectionEnum(),
        ];

        yield 'ReflectionEnumBackedCase' => [
            'expectedResult' => null,
            'expectedException' => null,
            'reflector' => self::reflectMethod(),
        ];

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
            'expectedResult' => null,
            'expectedException' => new InvalidArgumentException(
                'Cannot determine scope information for reflector of type ReflectionClassConstant',
            ),
            'reflector' => new ReflectionClassConstant(ObjectTestFixture::class, 'TEST'),
        ];

        yield 'ReflectionMethod' => [
            'expectedResult' => new Scope(
                file: 'basename://ObjectTestFixture.php',
                line: 35,
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
            ),
            'expectedException' => null,
            'reflector' => self::reflectMethod([ObjectTestFixture::class, 'greet']),
        ];

        yield 'ReflectionFunction' => [
            'expectedResult' => new Scope(
                file: 'basename://functions.php',
                line: 47,
                class: null,
                comment:  <<<'PHP'
                    /**
                     * @param 'hello'|'bye' $greeting
                     */
                    PHP,
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
