<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use PHPStan\PhpDocParser;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Factory as GenericsResolverFactory;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Resolver as GenericsResolver;
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
	private PhpImports\Resolver $phpImportsResolver;
	/** @readonly */
	private GenericsResolverFactory $genericsResolverFactory;

	public static function createInstance(): self
	{
		return new self();
	}

	public function __construct(
		?GenericsResolverFactory $genericTypesExporter = null,
		?ReflectorScopeResolver $scopeResolver = null,
		?PhpDocParser\Lexer\Lexer $phpDocParserLexer = null,
		?PhpDocParser\Parser\ConstExprParser $phpDocConstExprParser = null,
		?PhpDocParser\Parser\TypeParser $phpDocTypeParser = null,
		?PhpDocParser\Parser\PhpDocParser $phpDocParser = null,
		?PhpImports\Resolver $phpImportsResolver = null,
	) {
		$this->genericsResolverFactory = $genericTypesExporter ?? new GenericsResolverFactory($this);
		$this->scopeResolver = $scopeResolver ?? new ReflectorScopeResolver($this->genericsResolverFactory);
		$this->lexer = $phpDocParserLexer ?? new PhpDocParser\Lexer\Lexer();
		$constExprParser = $phpDocConstExprParser ?? new PhpDocParser\Parser\ConstExprParser();
		$typeParser = $phpDocTypeParser ?? new PhpDocParser\Parser\TypeParser($constExprParser);
		$this->parser = $phpDocParser ?? new PhpDocParser\Parser\PhpDocParser($typeParser, $constExprParser);
		$this->phpImportsResolver = $phpImportsResolver ?? new PhpImports\Resolver();
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
	 * @param null|GenericsResolver $genericsResolver List of generic types inherited by, but outside of, the current
	 *                                                scope. For example, from class-level in case of method scope.
	 */
	public function createFromComment(
		string   $comment,
		?string  $file = null,
		?int     $line = null,
		?string  $class = null,
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
			typeResolver: $typeResolver = new TypeResolver($this->genericsResolverFactory, $this->phpImportsResolver),
			genericTypesExtractor: $this->genericsResolverFactory->withResolvers($typeResolver, $scope->genericsResolver),
		);
	}
}
