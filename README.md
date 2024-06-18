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

## 🙄 Limitations

This library is designed to work with PHP files having at most one namespace (within the same file).
