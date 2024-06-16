<?php

namespace uuf6429\PHPStanPHPDocTypeResolver;

class PhpImportsResolver
{
    /** @var array<string, array{namespace: string, imports: array<string, string>}> */
    private array $cache = [];

    /**
     * @return array<string, string>
     */
    public function getImports(string $file): array
    {
        return ($this->cache[$file] ??= $this->loadFile($file))['imports'];
    }

    public function getNamespace(string $file): string
    {
        return ($this->cache[$file] ??= $this->loadFile($file))['namespace'];
    }

    /**
     * @return array{namespace: string, imports: array<string, string>}
     */
    private function loadFile(string $file): array
    {
        if (!is_file($file) || ($content = file_get_contents($file)) === false) {
            return ['namespace' => '', 'imports' => []];
        }

        return (new PhpImportsParser($content))->parse();
    }
}
