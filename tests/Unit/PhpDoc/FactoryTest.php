<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpDoc;

use PHPStan\PhpDocParser;
use PHPUnit\Framework\TestCase;
use Throwable;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\MultipleTagsFoundException;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\TagNotFoundException;
use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\ObjectTestFixture;
use uuf6429\PHPStanPHPDocTypeResolverTests\ReflectsValuesTrait;

class FactoryTest extends TestCase
{
    use ReflectsValuesTrait;

    /**
     * @throws Throwable
     */
    public function testThatDocBlockCanBeCreatedFromReflector(): void
    {
        $factory = Factory::createInstance();
        $reflector = self::reflectMethod([ObjectTestFixture::class, 'greet']);

        $block = $factory->createFromReflector($reflector);
        $tags = $block->getTags('@param');

        $this->assertEquals(
            [
                new PhpDocParser\Ast\PhpDoc\ParamTagValueNode(
                    type: new PhpDocParser\Ast\Type\UnionTypeNode([
                        new PhpDocParser\Ast\Type\IdentifierTypeNode('string'),
                        new PhpDocParser\Ast\Type\IdentifierTypeNode('Stringable'),
                    ]),
                    isVariadic: false,
                    parameterName: '$name',
                    description: '',
                ),
            ],
            $tags,
        );
    }

    public function testThatDocBlockCanBeCreatedFromComment(): void
    {
        $factory = Factory::createInstance();
        $comment = <<<'PHP'
            /**
             * @return string
             */
            PHP;

        $block = $factory->createFromComment($comment);
        $tags = $block->getTags('@return');

        $this->assertEquals(
            [
                new PhpDocParser\Ast\PhpDoc\ReturnTagValueNode(
                    type: new PhpDocParser\Ast\Type\IdentifierTypeNode('string'),
                    description: '',
                ),
            ],
            $tags,
        );
    }

    public function testThatRequiredMissingTagTriggersException(): void
    {
        $factory = Factory::createInstance();
        $reflector = self::reflectMethod([ObjectTestFixture::class, 'greet']);
        $block = $factory->createFromReflector($reflector);

        $this->expectException(TagNotFoundException::class);
        $this->expectExceptionMessage('The `@property` tag was not defined');

        $block->getTag('@property');
    }

    public function testThatRepeatedSingleTagTriggersException(): void
    {
        $factory = Factory::createInstance();
        $comment = <<<'PHP'
            /**
             * @param A
             * @param B
             */
            PHP;
        $block = $factory->createFromComment($comment);

        $this->expectException(MultipleTagsFoundException::class);
        $this->expectExceptionMessage('More than one `@param` tags have been defined');

        $block->getTag('@param');
    }

    public function testThatSummaryAndDescriptionWorks(): void
    {
        $factory = Factory::createInstance();
        $reflector = self::reflectMethod([ObjectTestFixture::class, 'greet']);

        $block = $factory->createFromReflector($reflector);

        $this->assertEquals('Greeter', $block->getSummary());
        $this->assertEquals(
            <<<'TEST'
            A function that greets the entity given their name with the desired greeting.
            For example, one could greet the world with `(new ObjectTestFixture('Hello'))->greet('World')`.
            TEST,
            $block->getDescription(),
        );
    }

    public function testThatTagExistenceCheckWorks(): void
    {
        $factory = Factory::createInstance();
        $comment = <<<'PHP'
            /**
             * @param A
             * @deprecated
             */
            PHP;
        $block = $factory->createFromComment($comment);

        $this->assertTrue($block->hasTag('@deprecated'));
        $this->assertFalse($block->hasTag('@readonly'));
    }
}
