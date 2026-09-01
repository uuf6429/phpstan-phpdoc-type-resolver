<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use PHPStan\PhpDocParser;
use PHPStan\PhpDocParser\ParserConfig;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics\Extractor as GenericsExtractor;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics\GenericTypeMap as GenericsResolver;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports;
use uuf6429\PHPStanPHPDocTypeResolver\TypeResolver;

/**
 * A handy class that sets up the various objects required for parsing PHPDoc blocks (and fully resolving types).
 */
class Factory
{
	/** @readonly */
	private ReflectorScopeResolver $scopeResolver;

	/** @readonly */
	private PhpDocParser\Lexer\Lexer $lexer;

	/** @readonly */
	private PhpDocParser\Parser\PhpDocParser $parser;

	/** @readonly */
	private GenericsExtractor $genericsExtractor;

	/** @readonly */
	private TypeResolver $typeResolver;

	public function __construct(
		?GenericsExtractor $genericsExtractor = null,
		?ReflectorScopeResolver $scopeResolver = null,
		?PhpDocParser\Lexer\Lexer $phpDocParserLexer = null,
		?PhpDocParser\Parser\ConstExprParser $phpDocConstExprParser = null,
		?PhpDocParser\Parser\TypeParser $phpDocTypeParser = null,
		?PhpDocParser\Parser\PhpDocParser $phpDocParser = null,
		?PhpImports\Resolver $phpImportsResolver = null,
	) {
		$this->genericsExtractor = $genericsExtractor ?? new GenericsExtractor($this);
		$this->scopeResolver = $scopeResolver ?? new ReflectorScopeResolver($this->genericsExtractor);
		$parserConfig = new ParserConfig([]);
		$this->lexer = $phpDocParserLexer ?? new PhpDocParser\Lexer\Lexer($parserConfig);
		$constExprParser = $phpDocConstExprParser ?? new PhpDocParser\Parser\ConstExprParser($parserConfig);
		$typeParser = $phpDocTypeParser ?? new PhpDocParser\Parser\TypeParser($parserConfig, $constExprParser);
		$this->parser = $phpDocParser ?? new PhpDocParser\Parser\PhpDocParser($parserConfig, $typeParser, $constExprParser);
		$this->typeResolver = new TypeResolver($this->genericsExtractor, $phpImportsResolver ?? new PhpImports\Resolver());
	}

	public static function createInstance(): self
	{
		return new self();
	}

	/**
	 * @throws \ReflectionException
	 */
	public function createFromReflector(\Reflector $reflector): Block
	{
		return $this->createFromScope($this->scopeResolver->resolve($reflector));
	}

	/**
	 * @param string                $comment          the PHPDoc block comment
	 * @param null|string           $file             the file where the comment appeared in
	 * @param null|int              $line             the (approximate) line where the comment appeared
	 * @param null|class-string     $class            fully-qualified name of the class that the comment applies to
	 * @param null|GenericsResolver $genericsResolver List of generic types inherited by, but outside of, the current
	 *                                                scope. For example, from class-level in case of method scope.
	 */
	public function createFromComment(
		string $comment,
		?string $file = null,
		?int $line = null,
		?string $class = null,
		?GenericsResolver $genericsResolver = null,
	): Block {
		return $this->createFromScope(new Scope(
			file: $file,
			line: $line,
			class: $class,
			comment: $comment,
			genericsResolver: $genericsResolver ?? new GenericsResolver(),
		));
	}

	public function createFromScope(Scope $scope): Block
	{
		return new Block(
			scope: $scope,
			docNode: $this->parser->parse(
				new PhpDocParser\Parser\TokenIterator(
					$this->lexer->tokenize(
						trim($scope->comment) !== '' ? $scope->comment : "/**\n */",
					),
				),
			),
			typeResolver: $this->typeResolver,
			genericsExtractor: $this->genericsExtractor,
		);
	}
}
