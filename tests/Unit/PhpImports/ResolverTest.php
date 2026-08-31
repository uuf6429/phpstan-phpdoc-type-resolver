<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpImports;

use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Generics\GenericTypeMap;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports\Resolver;

/**
 * @internal
 */
final class ResolverTest extends TestCase
{
	public function testInvalidFileResolvesSilently(): void
	{
		$resolver = new Resolver();
		$scope = new Scope('inexistentfile.php', null, null, '', new GenericTypeMap());

		$this->expectNotToPerformAssertions();

		$resolver->getImports($scope);
	}
}
