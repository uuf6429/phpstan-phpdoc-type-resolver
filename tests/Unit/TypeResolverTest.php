<?php

/**
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reflector;
use RuntimeException;
use SplFileInfo;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\ReflectorScopeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
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
        $docBlock = $this->parseDocBlock($scope->comment);

        $typeResolver = new TypeResolver($scope);
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
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnBoolOrInteger']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode('bool'),
                new Type\IdentifierTypeNode('integer'),
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
                    keyName: new ConstExprIntegerNode('1'),
                    optional: false,
                    valueType: new Type\GenericTypeNode(
                        type: new Type\IdentifierTypeNode('list'),
                        genericTypes: [
                            new Type\IdentifierTypeNode(Case1::class),
                        ],
                        variances: [
                            'invariant',
                        ],
                    ),
                ),
                new Type\ArrayShapeItemNode(
                    keyName: new ConstExprIntegerNode('2'),
                    optional: true,
                    valueType: new Type\ArrayTypeNode(
                        type: new Type\IdentifierTypeNode(Case2::class),
                    ),
                ),
            ]),
        ];

        yield 'return cases wrapped in object' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnCasesJumpingWrappedInObject']),
            'expectedReturnType' => new Type\ObjectShapeNode([
                new Type\ObjectShapeItemNode(
                    keyName: new ConstExprStringNode('jumpingCases'),
                    optional: false,
                    valueType: new Type\UnionTypeNode([
                        new Type\IdentifierTypeNode('null'),
                        new Type\ArrayTypeNode(
                            type: new Type\IntersectionTypeNode([
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
                parameterName: '$cond',
                targetType: new Type\IdentifierTypeNode('true'),
                if: new Type\IdentifierTypeNode('callable'),
                else: new Type\ConstTypeNode(new ConstExprStringNode('text')),
                negated: false,
            ),
        ];

        yield 'return int range' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnRandomInt']),
            'expectedReturnType' => new Type\GenericTypeNode(
                type: new Type\IdentifierTypeNode('int'),
                genericTypes: [
                    new Type\ConstTypeNode(new ConstExprIntegerNode('0')),
                    new Type\IdentifierTypeNode('max'),
                ],
                variances: [
                    'invariant',
                    'invariant',
                ],
            ),
        ];

        // TODO enable this when generics are fully supported
        /*yield 'return offset of virtual type' => [
            'reflector' => self::reflectMethod([TypeResolverTestFixture::class, 'returnPredefinedColor']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode('null'),
                new Type\OffsetAccessTypeNode(
                    type: new Type\IdentifierTypeNode('TColorKey'),
                    offset: new Type\IdentifierTypeNode('TColors'),
                ),
            ]),
        ];*/
    }

    public function testThatRelativeTypeWithoutClassScopeIsNotAllowed(): void
    {
        $scope = new Scope(
            file: null,
            line: null,
            class: null,
            comment: <<<'PHP'
                /**
                 * @return $this
                 */
                PHP,
        );
        $docBlock = $this->parseDocBlock($scope->comment);
        $typeResolver = new TypeResolver($scope);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot resolve `$this`, no class was defined in the current scope');

        $typeResolver->resolve($docBlock->getReturnTagValues()[0]->type);
    }

    public function testThatInvalidTypeIsIgnored(): void
    {
        $scope = new Scope(null, null, null, '');
        $typeResolver = new TypeResolver($scope);
        $invalidType = new Type\InvalidTypeNode(new ParserException('', 0, 0, 0));

        $processedType = $typeResolver->resolve($invalidType);

        $this->assertSame($invalidType, $processedType);
    }

    public function testThatUnsupportedTypesTriggerException(): void
    {
        $scope = new Scope(null, null, null, '');
        $typeResolver = new TypeResolver($scope);
        $unsupportedType = $this->createMock(Type\TypeNode::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve related types, type is unsupported: ' . get_class($unsupportedType));

        $typeResolver->resolve($unsupportedType);
    }
}
