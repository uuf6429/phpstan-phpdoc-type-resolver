<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImportsParser;

class PhpImportsParserTest extends TestCase
{
    /**
     * @param array{namespace: string, imports: array<string, string>} $expectedResult
     */
    #[DataProvider('parsingDataProvider')]
    public function testParsing(string $sourceCode, array $expectedResult): void
    {
        $parser = new PhpImportsParser("<?php\n\n$sourceCode");

        $actualResult = $parser->parse();

        $this->assertSame(
            $expectedResult,
            [
                'namespace' => $actualResult->getNamespaceAt(0),
                'imports' => $actualResult->getImportsAt(0),
            ],
        );
    }

    /**
     * @return iterable<array{sourceCode: string, expectedResult: array{namespace: string, imports: array<string, string>}}>
     */
    public static function parsingDataProvider(): iterable
    {
        yield 'no imports' => [
            'sourceCode' => <<<'PHP'
                class XX {}
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => [],
            ],
        ];

        yield 'simple import' => [
            'sourceCode' => <<<'PHP'
                use XX;
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => ['xx' => 'XX'],
            ],
        ];

        yield 'aliased import' => [
            'sourceCode' => <<<'PHP'
                use XX as YY;
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => ['yy' => 'XX'],
            ],
        ];

        yield 'import in namespace' => [
            'sourceCode' => <<<'PHP'
                namespace XX;
                use YY;
                PHP,
            'expectedResult' => [
                'namespace' => 'XX',
                'imports' => ['yy' => 'YY'],
            ],
        ];

        yield 'import group' => [
            'sourceCode' => <<<'PHP'
                use XX\{YY, ZZ};
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => [
                    'yy' => 'XX\YY',
                    'zz' => 'XX\ZZ',
                ],
            ],
        ];

        yield 'import group with alias' => [
            'sourceCode' => <<<'PHP'
                use XX\{YY\ZZ, ZZ as ZZ2};
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => [
                    'zz' => 'XX\YY\ZZ',
                    'zz2' => 'XX\ZZ',
                ],
            ],
        ];

        yield 'import multiple stuff inline' => [
            'sourceCode' => <<<'PHP'
                use XX\YY, YY\ZZ;
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => [
                    'yy' => 'XX\YY',
                    'zz' => 'YY\ZZ',
                ],
            ],
        ];

        yield 'import multiple stuff multiline' => [
            'sourceCode' => <<<'PHP'
                use XX\YY;
                use YY\ZZ;
                PHP,
            'expectedResult' => [
                'namespace' => '',
                'imports' => [
                    'yy' => 'XX\YY',
                    'zz' => 'YY\ZZ',
                ],
            ],
        ];
    }
}
