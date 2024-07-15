<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use PHPStan\PhpDocParser;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports;
use uuf6429\PHPStanPHPDocTypeResolver\ReflectorScopeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;
use uuf6429\PHPStanPHPDocTypeResolver\TypeScope;

class Factory
{
    private readonly ReflectorScopeResolver $scopeResolver;
    private readonly PhpDocParser\Lexer\Lexer $lexer;
    private readonly PhpDocParser\Parser\PhpDocParser $parser;
    private readonly PhpImports\Resolver $phpImportsResolver;

    public static function createInstance(): self
    {
        return new self();
    }

    public function __construct()
    {
        $this->scopeResolver = new ReflectorScopeResolver();
        $this->lexer = new PhpDocParser\Lexer\Lexer();
        $constExprParser = new PhpDocParser\Parser\ConstExprParser();
        $typeParser = new PhpDocParser\Parser\TypeParser($constExprParser);
        $this->parser = new PhpDocParser\Parser\PhpDocParser($typeParser, $constExprParser);
        $this->phpImportsResolver = new PhpImports\Resolver();
    }

    public function createFromReflector(Reflector $reflector): Block
    {
        return $this->createFromScope($this->scopeResolver->resolve($reflector));
    }

    /**
     * @param string $comment The PHPDoc block comment.
     * @param null|string $file The file where the comment appeared in.
     * @param null|int $line The (approximate) line where the comment appeared.
     * @param null|class-string $class Fully-qualified name of the class that the comment applies to.
     */
    public function createFromComment(string $comment, ?string $file = null, ?int $line = null, ?string $class = null): Block
    {
        return $this->createFromScope(new TypeScope(file: $file, line: $line, class: $class, comment: $comment));
    }

    public function createFromScope(TypeScope $scope): Block
    {
        return new Block(
            $this->parser->parse(
                new PhpDocParser\Parser\TokenIterator(
                    $this->lexer->tokenize(
                        trim($scope->comment) ? $scope->comment : "/**\n */",
                    ),
                ),
            ),
            new TypeResolver($scope, $this->phpImportsResolver),
        );
    }
}
