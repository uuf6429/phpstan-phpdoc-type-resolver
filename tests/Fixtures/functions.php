<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

use SplFileInfo;

/**
 * @return string
 */
function typeResolverTestFunctionReturningStringFixture(): string
{
	return 'hoi';
}

/**
 * @return SplFileInfo
 */
function typeResolverTestFunctionReturningImportedClass(): SplFileInfo
{
	return new \SplFileInfo(__FILE__);
}

/**
 * @return (\Closure(): string)
 */
function getTypeResolverTestClosureReturningString(): \Closure
{
	/**
	 * @return string
	 */
	return static function (): string {
		return 'hoi';
	};
}

/**
 * @return (\Closure(): \SplFileInfo)
 */
function getTypeResolverTestClosureReturningImportedType(): \Closure
{
	/**
	 * @return SplFileInfo
	 */
	return static function (): \SplFileInfo {
		return new \SplFileInfo(__FILE__);
	};
}

/**
 * @param 'bye'|'hello' $greeting
 */
function functionWithParameter(string $greeting): void
{
	echo $greeting;
}

/**
 * @return callable-string
 * @phpstan-ignore missingType.callable
 */
function getFunctionWithParameter(): string
{
	return __NAMESPACE__ . '\functionWithParameter';
}
