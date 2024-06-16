<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

use Closure;
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
    return new SplFileInfo(__FILE__);
}

function getTypeResolverTestClosureReturningString(): Closure
{
    /**
     * @return string
     */
    return static function (): string {
        return 'hoi';
    };
}

function getTypeResolverTestClosureReturningImportedType(): Closure
{
    /**
     * @return SplFileInfo
     */
    return static function (): SplFileInfo {
        return new SplFileInfo(__FILE__);
    };
}
