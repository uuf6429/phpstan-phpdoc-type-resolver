# 📡 PHPStan PHPDoc Type Resolver

[![CI](https://github.com/uuf6429/phpstan-phpdoc-type-resolver/actions/workflows/ci.yml/badge.svg)](https://github.com/uuf6429/phpstan-phpdoc-type-resolver/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/uuf6429/phpstan-phpdoc-type-resolver/branch/main/graph/badge.svg)](https://codecov.io/gh/uuf6429/phpstan-phpdoc-type-resolver)
[![Minimum PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-428F7E.svg)](https://github.com/uuf6429/phpstan-phpdoc-type-resolver/blob/main/LICENSE)
[![Latest Stable Version](https://poser.pugx.org/uuf6429/phpstan-phpdoc-type-resolver/v)](https://packagist.org/packages/uuf6429/phpstan-phpdoc-type-resolver)
[![Latest Unstable Version](https://poser.pugx.org/uuf6429/phpstan-phpdoc-type-resolver/v/unstable)](https://packagist.org/packages/uuf6429/phpstan-phpdoc-type-resolver)

Resolve (fully-qualify) types from PHPStan's PHPDoc parser.

## 💾 Installation

This package can be installed with [Composer](https://getcomposer.org), simply run the following:

```shell
composer require uuf6429/phpstan-phpdoc-type-resolver
```

_Consider using `--dev` if you intend to use this library during development only._

## 🚀 Usage

In principle the resolver needs two things:
1. The PHPStan-PHPDoc type (an instance of [TypeNode](https://github.com/phpstan/phpdoc-parser/blob/1.23.x/src/Ast/Type/TypeNode.php)).
2. 'Scope' information of where that type occurred.

There are two ways to retrieve that information, as shown below.

### 😎 Via Reflection

Let's assume we have a `\My\Project\Greeter` class with a `greet` method, here's how we can resolve that method's return type:

```php
// Reflect our class method
$reflector = new \ReflectionMethod(\My\Project\Greeter::class, 'greet');

// Use the scope resolver to get information about that method
$scopeResolver = new \uuf6429\PHPStanPHPDocTypeResolver\ReflectorScopeResolver();
$scope = $scopeResolver->resolve($reflector);

// Parse the PHPDoc block with PHPStan PHPDoc parser
$lexer = new \PHPStan\PhpDocParser\Lexer\Lexer();
$constExprParser = new \PHPStan\PhpDocParser\Parser\ConstExprParser();
$typeParser = new \PHPStan\PhpDocParser\Parser\TypeParser($constExprParser);
$parser = new \PHPStan\PhpDocParser\Parser\PhpDocParser($typeParser, $constExprParser);
$docBlock = $parser->parse(
    new \PHPStan\PhpDocParser\Parser\TokenIterator(
        $lexer->tokenize($scope->comment)     // <- note that the scope resolver also retrieves the PHPDoc block for us
    )
);

// Finally, we initialize the type resolver and resolve the first return type of the doc block
$typeResolver = new \uuf6429\PHPStanPHPDocTypeResolver\TypeResolver($scope);
$finalReturnType = $typeResolver->resolve($docBlock->getReturnTagValues()[0]->type);
```

### 🤪 Via Source Strings

It's also possible to resolve the type without actually loading the PHP source code (which is a requirement for
reflection). However, this will take more work - the main difference is that you will need to set up the scope yourself.

Let's assume we want to resolve a type in a PHP source code string:

```php
$source = <<<'PHP'
<?php

namespace My\Project\Services;

use My\Project\PersonEntity as Person;

class Greeter {
    /**
     * @param Person|object{name: string} $person
     */
    public function greet($person): void {
        echo "Hello, {$person->name}!";
    }
}

PHP;

// Construct the scope manually - automating this will take some work
$scope = new \uuf6429\PHPStanPHPDocTypeResolver\TypeScope(
    // In-memory file; you could also use php memory streams etc
    file: 'data:base64,' . base64_encode($source),
    // approximate line where the type has occurred - everything else below has to be specified manually
    line: 73,
    class: 'My\Project\Services\Greeter',
    comment: <<<'PHPDOC'
        /**
         * @param Person|object{name: string} $person
         */
        PHPDOC
);

// The rest of the code is the same as "Via Reflection" starting at the "$lexer = ..." line.
```
