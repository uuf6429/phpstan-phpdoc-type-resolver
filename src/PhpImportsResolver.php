<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

class PhpImportsResolver
{
    /** @var array<string, PhpImportsFile> */
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

    private function getFile(?string $file): PhpImportsFile
    {
        return $this->cache[$file] ??= $this->loadFile($file);
    }

    private function loadFile(?string $file): PhpImportsFile
    {
        if (!$file || !is_file($file) || ($content = file_get_contents($file)) === false) {
            return new PhpImportsFile([]);
        }

        return (new PhpImportsParser($content))->parse();
    }
}
