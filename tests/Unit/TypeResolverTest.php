<?php

/**
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reflector;
use RuntimeException;
use SplFileInfo;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\ConcreteGenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\TemplateGenericTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Types\VirtualTypeNode;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;
use uuf6429\PHPStanPHPDocTypeResolverTests\ReflectsValuesTrait;
use function uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\getTypeResolverTestClosureReturningImportedType;
use function uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\getTypeResolverTestClosureReturningString;

class TypeResolverTest extends TestCase
{
    use ReflectsValuesTrait;

    #[DataProvider('returnTypeDataProvider')]
    public function testReturnType(Reflector $reflector, ?Type\TypeNode $expectedReturnType): void
    {
        $docBlock = Factory::createInstance()->createFromReflector($reflector);

        /** @var ReturnTagValueNode $returnTag */
        $returnTag = $docBlock->getTag('@return');

        $this->assertEquals($expectedReturnType, $returnTag->type);
    }

    /**
     * @return iterable<string, array{reflector: Reflector, expectedReturnType: ?Type\TypeNode}>
     */
    public static function returnTypeDataProvider(): iterable
    {
        yield 'return void' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnVoid']),
            'expectedReturnType' => new Type\IdentifierTypeNode('void'),
        ];

        yield 'return nullable string' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnNullableString']),
            'expectedReturnType' => new Type\NullableTypeNode(
                new Type\IdentifierTypeNode('string'),
            ),
        ];

        yield 'return boolean or integer' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnBoolOrInteger']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode('bool'),
                new Type\IdentifierTypeNode('integer'),
            ]),
        ];

        yield 'return namespaced class relative to current namespace' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnImplicitNamespaceClass']),
            'expectedReturnType' => new Type\IdentifierTypeNode(Fixtures\Cases\Case1::class),
        ];

        yield 'return grouped namespace import' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnImportedGroupedNamespaceClass']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode(Fixtures\Cases\Case1::class),
                new Type\IdentifierTypeNode(Fixtures\Cases\Case2::class),
            ]),
        ];

        yield 'return self' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnSelf']),
            'expectedReturnType' => new Type\IdentifierTypeNode(Fixtures\TypeResolverTestFixture::class),
        ];

        yield 'return static' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnStatic']),
            'expectedReturnType' => new Type\IdentifierTypeNode(Fixtures\TypeResolverTestFixture::class),
        ];

        yield 'return this' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnThis']),
            'expectedReturnType' => new Type\IdentifierTypeNode(Fixtures\TypeResolverTestFixture::class),
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
            'reflector' => self::reflectFunction(Fixtures\TypeResolverTestFixture::getTypeResolverTestClosureReturningString()),
            'expectedReturnType' => new Type\IdentifierTypeNode('string'),
        ];

        yield 'return cases grouped by string key' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnArrayOfGroupedCases']),
            'expectedReturnType' => new Type\ArrayShapeNode([
                new Type\ArrayShapeItemNode(
                    keyName: new ConstExprIntegerNode('1'),
                    optional: false,
                    valueType: new ConcreteGenericTypeNode(
                        type: new Type\IdentifierTypeNode('list'),
                        genericTypes: [
                            new Type\IdentifierTypeNode(Fixtures\Cases\Case1::class),
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
                        type: new Type\IdentifierTypeNode(Fixtures\Cases\Case2::class),
                    ),
                ),
            ]),
        ];

        yield 'return cases wrapped in object' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnCasesJumpingWrappedInObject']),
            'expectedReturnType' => new Type\ObjectShapeNode([
                new Type\ObjectShapeItemNode(
                    keyName: new ConstExprStringNode('jumpingCases'),
                    optional: false,
                    valueType: new Type\UnionTypeNode([
                        new Type\IdentifierTypeNode('null'),
                        new Type\ArrayTypeNode(
                            type: new Type\IntersectionTypeNode([
                                new Type\IdentifierTypeNode(Fixtures\Cases\Case1::class),
                                new Type\IdentifierTypeNode(Fixtures\Cases\JumpingCaseInterface::class),
                            ]),
                        ),
                    ]),
                ),
            ]),
        ];

        yield 'return callable or string conditionally' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnCallableOrTextConditionally']),
            'expectedReturnType' => new Type\ConditionalTypeForParameterNode(
                parameterName: '$cond',
                targetType: new Type\IdentifierTypeNode('true'),
                if: new Type\IdentifierTypeNode('callable'),
                else: new Type\ConstTypeNode(new ConstExprStringNode('text')),
                negated: false,
            ),
        ];

        yield 'return int range' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnRandomInt']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
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

        yield 'generic class creator' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'createClass']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
                type: new Type\IdentifierTypeNode('new'),
                genericTypes: [
                    new Type\IdentifierTypeNode('object'),
                ],
                variances: [
                    'invariant',
                ],
            ),
        ];

        yield 'return offset of virtual type' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'translateColor']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\IdentifierTypeNode('null'),
                new Type\OffsetAccessTypeNode(
                    // TODO key-of below is wrong, it should be a more specialized (parsed) node, but it probably won't impact us much in the short term.
                    type: new Type\IdentifierTypeNode('key-of<TColors>'),
                    offset: new VirtualTypeNode(
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
                        declaringClass: Fixtures\TypeResolverTestFixture::class,
                    ),
                ),
            ]),
        ];

        yield 'return callable with typed args' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnCallableWithTypedArgs']),
            'expectedReturnType' => new Type\CallableTypeNode(
                identifier: new Type\IdentifierTypeNode('callable'),
                parameters: [
                    new Type\CallableTypeParameterNode(
                        type: new Type\IdentifierTypeNode('int'),
                        isReference: false,
                        isVariadic: false,
                        parameterName: '',
                        isOptional: false,
                    ),
                    new Type\CallableTypeParameterNode(
                        type: new Type\IdentifierTypeNode('bool'),
                        isReference: false,
                        isVariadic: false,
                        parameterName: '$named',
                        isOptional: false,
                    ),
                ],
                returnType: new Type\IdentifierTypeNode('string'),
                templateTypes: [],
            ),
        ];

        yield 'return callable with templates' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnCallableWithTemplates']),
            'expectedReturnType' => new Type\CallableTypeNode(
                identifier: new Type\IdentifierTypeNode('callable'),
                parameters: [],
                returnType: new Type\IdentifierTypeNode('T'),
                templateTypes: [
                    new TemplateTagValueNode(
                        name: 'T',
                        bound: null,
                        description: '',
                        default: null,
                    ),
                ],
            ),
        ];

        yield 'return a class constant' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnOneClassConstant']),
            'expectedReturnType' => new Type\UnionTypeNode([
                new Type\ConstTypeNode(
                    constExpr: new ConstFetchNode(
                        className: Fixtures\TypeResolverTestFixture::class,
                        name: 'TYPE_A',
                    ),
                ),
                new Type\ConstTypeNode(
                    constExpr: new ConstFetchNode(
                        className: Fixtures\TypeResolverTestFixture::class,
                        name: 'TYPE_B',
                    ),
                ),
            ]),
        ];

        yield 'return all class constants' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverTestFixture::class, 'returnAllClassConstants']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
                type: new Type\IdentifierTypeNode('list'),
                genericTypes: [
                    new Type\ConstTypeNode(
                        constExpr: new ConstFetchNode(
                            className: Fixtures\TypeResolverTestFixture::class,
                            name: 'TYPE_*',
                        ),
                    ),
                ],
                variances: [
                    'invariant',
                ],
            ),
        ];

        yield 'return parent class' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverChildTestFixture::class, 'returnParent']),
            'expectedReturnType' => new Type\IdentifierTypeNode(Fixtures\TypeResolverTestFixture::class),
        ];

        yield 'return Payload<Number>' => [
            'reflector' => self::reflectMethod([Fixtures\Payload::class, 'makeNumberPayload']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
                type: new Type\IdentifierTypeNode(Fixtures\Payload::class),
                genericTypes: [
                    new Type\IdentifierTypeNode(Fixtures\Number::class),
                ],
                variances: [
                    'invariant',
                ],
            ),
        ];

        yield 'return Payload<Payload<T>>' => [
            'reflector' => self::reflectMethod([Fixtures\Payload::class, 'makePayloadPayload']),
            'expectedReturnType' => new TemplateGenericTypeNode(
                type: new Type\IdentifierTypeNode(Fixtures\Payload::class),
                genericTypes: [
                    new TemplateGenericTypeNode(
                        type: new Type\IdentifierTypeNode(Fixtures\Payload::class),
                        genericTypes: [
                            new Type\IdentifierTypeNode('T'),
                        ],
                        variances: [
                            'invariant',
                        ],
                    ),
                ],
                variances: [
                    'invariant',
                ],
            ),
        ];

        yield 'return Pair<int, string>' => [
            'reflector' => self::reflectMethod([Fixtures\Pair::class, 'makeArrayString']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
                type: new Type\IdentifierTypeNode(Fixtures\Pair::class),
                genericTypes: [
                    new Type\IdentifierTypeNode('int'),
                    new Type\IdentifierTypeNode('string'),
                ],
                variances: [
                    'invariant',
                    'invariant',
                ],
            ),
        ];

        yield 'return Pair<int, T of mixed>' => [
            'reflector' => self::reflectMethod([Fixtures\Pair::class, 'makeArrayValue']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
                type: new Type\IdentifierTypeNode(Fixtures\Pair::class),
                genericTypes: [
                    new Type\IdentifierTypeNode('int'),
                    new Type\IdentifierTypeNode('mixed'),
                ],
                variances: [
                    'invariant',
                    'invariant',
                ],
            ),
        ];

        yield 'return Pair<T, T>' => [
            'reflector' => self::reflectMethod([Fixtures\Pair::class, 'makeTwins']),
            'expectedReturnType' => new ConcreteGenericTypeNode(
                type: new Type\IdentifierTypeNode(Fixtures\Pair::class),
                genericTypes: [
                    new Type\IdentifierTypeNode('mixed'),
                    new Type\IdentifierTypeNode('mixed'),
                ],
                variances: [
                    'invariant',
                    'invariant',
                ],
            ),
        ];

        yield 'return T' => [
            'reflector' => self::reflectMethod([Fixtures\TypeResolverChildTestFixture::class, 'getSimilarItems']),
            'expectedReturnType' => new TemplateGenericTypeNode(
                type: new Type\IdentifierTypeNode('list'),
                genericTypes: [
                    new Type\IdentifierTypeNode('TItem'),
                ],
                variances: [
                    'invariant',
                ],
            ),
        ];
    }

    public function testThatRelativeTypeWithoutClassScopeIsNotAllowed(): void
    {
        $docBlock = Factory::createInstance()
            ->createFromComment(
                <<<'PHP'
                /**
                 * @return $this
                 */
                PHP,
            );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot resolve `$this`, no class was defined in the current scope');

        $docBlock->getTag('@return');
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

    public function testThatParentlessClassCannotResolveParent(): void
    {
        $docBlock = Factory::createInstance()
            ->createFromComment(
                <<<'PHP'
                /**
                 * @return parent
                 */
                PHP,
                class: Fixtures\TypeResolverTestFixture::class,
            );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Class/type `' . Fixtures\TypeResolverTestFixture::class . '` doesn\'t have a parent');

        $docBlock->getTag('@return');
    }

    public function testThatLocalTypeDefRequiresClass(): void
    {
        $docBlock = Factory::createInstance()
            ->createFromComment(
                <<<'PHP'
                /**
                 * @phpstan-type TExample string
                 * @return TExample
                 */
                PHP,
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHPStan local type requires a class');

        $docBlock->getTag('@return');
    }
}
