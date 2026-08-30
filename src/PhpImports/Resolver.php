<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpImports;

use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\Scope;

/**
 * A helper class that provides information about "current imports" and the "current namespace" at any given line in a
 * PHP source code file.
 */
class Resolver
{
	/** @var array<string, File> */
	private array $cache = [];

	/**
	 * @return array<string, string>
	 */
	public function getImports(Scope $scope): array
	{
		return $this->getFile((string)$scope->file)->getImportsAt($scope->line);
	}

	public function getNamespace(Scope $scope): string
	{
		return $this->getFile((string)$scope->file)->getNamespaceAt($scope->line);
	}

	private function getFile(string $file): File
	{
		return $this->cache[$file] ??= $this->loadFile($file);
	}

	private function loadFile(string $file): File
	{
		if (($content = @file_get_contents($file)) === false) {
			return new File([]);
		}

		return (new Parser($content))->parse();
	}
}
