# 📡 PHPStan PHPDoc Type Resolver

[![CI](https://github.com/uuf6429/phpstan-phpdoc-type-resolver/actions/workflows/ci.yml/badge.svg)](https://github.com/uuf6429/phpstan-phpdoc-type-resolver/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/uuf6429/phpstan-phpdoc-type-resolver/branch/main/graph/badge.svg)](https://codecov.io/gh/uuf6429/phpstan-phpdoc-type-resolver)
[![Minimum PHP Version](https://img.shields.io/badge/php-%5E8-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-428F7E.svg)](https://github.com/uuf6429/phpstan-phpdoc-type-resolver/blob/main/LICENSE)
[![Latest Stable Version](https://poser.pugx.org/uuf6429/phpstan-phpdoc-type-resolver/v)](https://packagist.org/packages/uuf6429/phpstan-phpdoc-type-resolver)
[![Latest Unstable Version](https://poser.pugx.org/uuf6429/phpstan-phpdoc-type-resolver/v/unstable)](https://packagist.org/packages/uuf6429/phpstan-phpdoc-type-resolver)

Resolve (fully qualify) types from PHPStan's PHPDoc parser.

## 💾 Installation

Install via [Composer](https://getcomposer.org):

```shell
composer require uuf6429/phpstan-phpdoc-type-resolver
```

_Consider using `--dev` if you intend to use this library during development only._

## 🤔 Why?

Because `phpstan/phpdoc-parser` doesn't resolve types (it's not its responsibility) and `phpdocument/type-resolver`
[currently has some major limitations](https://github.com/phpDocumentor/ReflectionDocBlock/issues/372).

## 🚀 Usage

In principle, the resolver needs two things:

1. The PHPStan-PHPDoc type (an instance of [`TypeNode`]).
2. 'Scope' information of where that type occurred.

There are two ways to retrieve that information, as shown below.

**Important:** The resolver will always convert some specific PHPStan types into something else as follows:

- *[`ThisTypeNode`] is converted into [`IdentifierTypeNode`] for the current class.
- *[`GenericTypeNode`] to either [`ConcreteGenericTypeNode`] or [`TemplateGenericTypeNode`] based on if the received
  instance contains unresolved generic/template types.
- PHPStan locally defined or imported types, a [`TypeDefTypeNode`] will be provided (instead of an
  [`IdentifierTypeNode`] with just the type name).

(*) conversion is mandatory, failures will trigger some sort of exception (meaning: the original type _should_ never be
returned).

### 😎 Via Reflection

Here's how we can resolve the [`Greeter::greet()`] method's return type:

```php
// Reflect our class method
$reflector = new \ReflectionMethod(\uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Greeter::class, 'greet');

// Use the provided factory to easily parse the PHPDoc, which additionally automatically resolves the types
$docBlock = \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory::createInstance()
    ->createFromReflector($reflector);

// And finally, retrieve the resolved type of the param tag
$paramTag = $docBlock->getTags('@param')[0];
assert((string)$paramTag->type === '(uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Person | object{name: string})');
```

### 🙈 Without Factory/DocBlock Wrapper

Here's the longer way to resolve the return type of the [`Greeter::greet()`] method:

```php
// Reflect our class method
$reflector = new \ReflectionMethod(\uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Greeter::class, 'greet');

// Use the scope resolver to get information about that method
$phpDocResolverFactory = new \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory();
$genericsResolverFactory = new \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Factory($phpDocResolverFactory);
$scopeResolver = new \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\ReflectorScopeResolver($genericsResolverFactory);
$scope = $scopeResolver->resolve($reflector);

// Parse the PHPDoc block with PHPStan PHPDoc parser
$lexer = new \PHPStan\PhpDocParser\Lexer\Lexer();
$constExprParser = new \PHPStan\PhpDocParser\Parser\ConstExprParser();
$typeParser = new \PHPStan\PhpDocParser\Parser\TypeParser($constExprParser);
$parser = new \PHPStan\PhpDocParser\Parser\PhpDocParser($typeParser, $constExprParser);
$docBlock = $parser->parse(
    new \PHPStan\PhpDocParser\Parser\TokenIterator(
        $lexer->tokenize($scope->comment)   // 👈 note that the scope resolver also retrieves the PHPDoc block for us
    )
);

// Finally, we initialize the type resolver and resolve the param type of the first param
$typeResolver = new \uuf6429\PHPStanPHPDocTypeResolver\TypeResolver();
$paramType = $typeResolver->resolve($scope, $docBlock->getParamTagValues()[0]->type);
assert((string)$paramType === '(uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Person | object{name: string})');
```

### 🤪 Via Source Strings

It's also possible to resolve the type without actually loading the PHP source code (which is a requirement for
reflection). However, this will take more work – the main difference is that you will need to set up the scope yourself.

Let's assume we want to resolve a type in a PHP source code string:

```php
$source = <<<'PHP'
<?php

namespace My\Project\Services;

use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Person as PersonEntity;

class Greeter {
    /**
     * @param PersonEntity|object{name: string} $person
     */
    public function greet($person): void {
        echo "Hello, {$person->name}!";
    }
}

PHP;

// Construct the scope manually - automating this will take some work
$scope = new \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope(
    // In-memory file; you could also use php memory streams etc
    file: 'data:application/x-httpd-php;base64,' . base64_encode($source),
    // Approximate line where the type occurred
    line: 9,
    // The class within which the type occurred
    class: 'My\Project\Services\Greeter',
    // The actualy PHPDoc block containing the type we're interested in
    comment: "/**\n * @param PersonEntity|object{name: string} \$person\n */",
    genericsResolver: new \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Resolver(),
);

// The factory can also be used with a custom scope
$docBlock = \uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Factory::createInstance()
    ->createFromScope($scope);

// And as before, retrieve the resolved type of the param tag
$paramTag = $docBlock->getTags('@param')[0];
assert((string)$paramTag->type === '(uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\Person | object{name: string})');
```

---

[`TypeNode`]: https://github.com/phpstan/phpdoc-parser/blob/1.23.x/src/Ast/Type/TypeNode.php
[`ThisTypeNode`]: https://github.com/phpstan/phpdoc-parser/blob/1.23.x/src/Ast/Type/ThisTypeNode.php
[`IdentifierTypeNode`]: https://github.com/phpstan/phpdoc-parser/blob/1.23.x/src/Ast/Type/IdentifierTypeNode.php
[`GenericTypeNode`]: https://github.com/phpstan/phpdoc-parser/blob/1.23.x/src/Ast/Type/GenericTypeNode.php
[`ConcreteGenericTypeNode`]: https://github.com/uuf6429/phpstan-phpdoc-type-resolver/blob/main/src/PhpDoc/Types/ConcreteGenericTypeNode.php
[`TemplateGenericTypeNode`]: https://github.com/uuf6429/phpstan-phpdoc-type-resolver/blob/main/src/PhpDoc/Types/TemplateGenericTypeNode.php
[`TypeDefTypeNode`]: https://github.com/uuf6429/phpstan-phpdoc-type-resolver/blob/main/src/PhpDoc/Types/TypeDefTypeNode.php
[`Greeter::greet()`]: https://github.com/uuf6429/phpstan-phpdoc-type-resolver/blob/main/tests/Fixtures/Greeter.php
