<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

abstract class TypeResolverChildTestFixture extends TypeResolverTestFixture
{
	/**
	 * @return parent
	 */
	abstract public function returnParent(): object;
}
