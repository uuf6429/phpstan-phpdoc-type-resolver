<?php

/**
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reflector;
use SplFileInfo;
use uuf6429\PHPStanPHPDocTypeResolver\ReflectorScopeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Cases\Case1;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Cases\Case2;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Cases\JumpingCaseInterface;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\TypeResolverTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\ParsesDocBlocksTrait;
use uuf6429\PHPStanPHPDocTypeResolverTests\ReflectsValuesTrait;

use function uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\getTypeResolverTestClosureReturningImportedType;
use function uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\getTypeResolverTestClosureReturningString;

class TypeResolverTest extends TestCase
{
    use ReflectsValuesTrait;
    use ParsesDocBlocksTrait;

    #[DataProvider('returnTypeDataProvider')]
    public function testReturnType(Reflector $reflector, ?Type\TypeNode $expectedReturnType): void
    {
        $scopeResolver = new ReflectorScopeResolver();
        $scope = $scopeResolver->resolve($reflector);
        $docBlock = $this->parseDocBlock($scope['comment']);

        $typeResolver = new TypeResolver($scope['file'], $scope['class']);
        $actualReturnType = $typeResolver->resolve($docBlock->getReturnTagValues()[0]->type);

        $this->assertEquals($expectedReturnType, $actualReturnType);
    }

    /**
     * @return iterable<string, array{reflector: Reflector, expectedReturnType: ?Type\TypeNode}>
     */
    public static function returnTypeDataProvider(): iterable
    {
        yield 'return void' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnVoid']),
            'expectedReturnType' => new Type\IdentifierTypeNode('void'),
        ];

        yield 'return nullable string' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnNullableString']),
            'expectedReturnType' => new Type\NullableTypeNode(
                new Type\IdentifierTypeNode('string'),
            ),
        ];

        yield 'return boolean or integer' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnBoolOrInt']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode('bool'),
                new Type\IdentifierTypeNode('int'),
            ]),
        ];

        yield 'return namespaced class relative to current namespace' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnImplicitNamespaceClass']),
            'expectedReturnType' => new Type\IdentifierTypeNode(Case1::class),
        ];

        yield 'return grouped namespace import' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnImportedGroupedNamespaceClass']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode(Case1::class),
                new Type\IdentifierTypeNode(Case2::class),
            ]),
        ];

        yield 'return self' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnSelf']),
            'expectedReturnType' => new Type\IdentifierTypeNode(TypeResolverTestFixture::class),
        ];

        yield 'return static' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnStatic']),
            'expectedReturnType' => new Type\IdentifierTypeNode(TypeResolverTestFixture::class),
        ];

        yield 'return this' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnThis']),
            'expectedReturnType' => new Type\IdentifierTypeNode(TypeResolverTestFixture::class),
        ];

        yield 'return string from function' => [
            'reflector' => self::reflectFunction(
                'uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\typeResolverTestFunctionReturningStringFixture',
            ),
            'expectedReturnType' => new Type\IdentifierTypeNode('string'),
        ];

        yield 'return imported class from function' => [
            'reflector' => self::reflectFunction(
                'uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\typeResolverTestFunctionReturningImportedClass',
            ),
            'expectedReturnType' => new Type\IdentifierTypeNode(SplFileInfo::class),
        ];

        yield 'return string from closure from function' => [
            'reflector' => self::reflectFunction(getTypeResolverTestClosureReturningString()),
            'expectedReturnType' => new Type\IdentifierTypeNode('string'),
        ];

        yield 'return imported type from closure from function' => [
            'reflector' => self::reflectFunction(getTypeResolverTestClosureReturningImportedType()),
            'expectedReturnType' => new Type\IdentifierTypeNode('SplFileInfo'),
        ];

        yield 'return string from closure from method' => [
            'reflector' => self::reflectFunction(TypeResolverTestFixture::getTypeResolverTestClosureReturningString()),
            'expectedReturnType' => new Type\IdentifierTypeNode('string'),
        ];

        yield 'return cases grouped by string key' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnArrayOfGroupedCases']),
            'expectedReturnType' => new Type\ArrayShapeNode([
                new Type\ArrayShapeItemNode(
                    new ConstExprIntegerNode('1'),
                    false,
                    new Type\GenericTypeNode(
                        new Type\IdentifierTypeNode('list'),
                        [new Type\IdentifierTypeNode(Case1::class)],
                        ['invariant'],
                    ),
                ),
                new Type\ArrayShapeItemNode(
                    new ConstExprIntegerNode('2'),
                    true,
                    new Type\ArrayTypeNode(
                        new Type\IdentifierTypeNode(Case2::class),
                    ),
                ),
            ]),
        ];

        yield 'return cases wrapped in object' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnCasesJumpingWrappedInObject']),
            'expectedReturnType' => new Type\ObjectShapeNode([
                new Type\ObjectShapeItemNode(
                    new ConstExprStringNode('jumpingCases'),
                    false,
                    new Type\UnionTypeNode([
                        new Type\IdentifierTypeNode('null'),
                        new Type\ArrayTypeNode(
                            new Type\IntersectionTypeNode([
                                new Type\IdentifierTypeNode(Case1::class),
                                new Type\IdentifierTypeNode(JumpingCaseInterface::class),
                            ]),
                        ),
                    ]),
                ),
            ]),
        ];

        yield 'return callable or string conditionally' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnCallableOrTextConditionally']),
            'expectedReturnType' => new Type\ConditionalTypeForParameterNode(
                '$cond',
                new Type\IdentifierTypeNode('true'),
                new Type\IdentifierTypeNode('callable'),
                new Type\ConstTypeNode(new ConstExprStringNode('text')),
                false,
            ),
        ];
    }
}
