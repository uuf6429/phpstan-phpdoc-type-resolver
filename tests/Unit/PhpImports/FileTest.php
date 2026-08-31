<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit\PhpImports;

use PHPUnit\Framework\TestCase;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports\Block;
use uuf6429\PHPStanPHPDocTypeResolver\PhpImports\File;

/**
 * @internal
 */
final class FileTest extends TestCase
{
	/**
	 * @testWith [null, ""]
	 *           [1, ""]
	 *           [4, "App"]
	 *           [16, "App"]
	 *           [18, ""]
	 *           [19, "App\\Admin"]
	 *           [20, "App\\Admin"]
	 *           [32, "App\\Admin"]
	 *           [34, ""]
	 */
	public function testGetNamespaceAt(?int $line, string $expectedNamespace): void
	{
		$file = new File([
			new Block(
				startLine: 3,
				endLine: 17,
				namespace: 'App',
				imports: [
					'App\Models\User' => 'User',
					'App\Services\AuthService' => 'Auth',
				],
			),
			new Block(
				startLine: 19,
				endLine: 33,
				namespace: 'App\Admin',
				imports: [
					'App\Models\Admin' => 'Admin',
					'App\Services\AuditService' => 'Audit',
				],
			),
		]);

		$actualNamespace = $file->getNamespaceAt($line);

		$this->assertSame($expectedNamespace, $actualNamespace);
	}
}
