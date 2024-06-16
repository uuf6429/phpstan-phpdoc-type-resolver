<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests;

use PHPStan;

trait ParsesDocBlocksTrait
{
    private function parseDocBlock(string $docBlock): PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode
    {
        $lexer = new PHPStan\PhpDocParser\Lexer\Lexer();
        $constExprParser = new PHPStan\PhpDocParser\Parser\ConstExprParser();
        $typeParser = new PHPStan\PhpDocParser\Parser\TypeParser($constExprParser);
        $parser = new PHPStan\PhpDocParser\Parser\PhpDocParser($typeParser, $constExprParser);

        return $parser->parse(
            new PHPStan\PhpDocParser\Parser\TokenIterator(
                $lexer->tokenize($docBlock),
            ),
        );
    }
}
