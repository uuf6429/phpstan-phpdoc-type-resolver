<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpImports;

use uuf6429\PHPStanPHPDocTypeResolver\TypeScope;

class Resolver
{
    /** @var array<string, File> */
    private array $cache = [];

    /**
     * @return array<string, string>
     */
    public function getImports(TypeScope $scope): array
    {
        return $this->getFile($scope->file)->getImportsAt($scope->line);
    }

    public function getNamespace(TypeScope $scope): string
    {
        return $this->getFile($scope->file)->getNamespaceAt($scope->line);
    }

    private function getFile(?string $file): File
    {
        return $this->cache[$file] ??= $this->loadFile($file);
    }

    private function loadFile(?string $file): File
    {
        if (!$file || !is_file($file) || ($content = file_get_contents($file)) === false) {
            return new File([]);
        }

        return (new Parser($content))->parse();
    }
}
