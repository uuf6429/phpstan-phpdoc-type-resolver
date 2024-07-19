<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpImports;

use LogicException;
use PhpToken;

/**
 * Parses a PHP source code file into a {@see Block}s of namespaces contained with a single {@see File}.
 */
final class Parser
{
    /**
     * @var array<PhpToken>
     */
    private readonly array $tokens;

    private readonly int $numTokens;

    private int $pointer = 0;

    public function __construct(string $content)
    {
        $this->tokens = PhpToken::tokenize($content);
        $this->numTokens = count($this->tokens);
    }

    public function parse(): File
    {
        $blocks = [];

        $namespace = null;
        $imports = [];
        $lastLine = 0;
        $lastToken = null;
        while ($token = $this->next()) {
            switch (true) {
                case $token->is(T_USE):
                    $namespace ??= '';
                    foreach ($this->parseUseStatement() as $k => $v) {
                        $imports[$k] = $v;
                    }
                    break;

                case $token->is(T_NAMESPACE):
                    if ($namespace !== null) {
                        $blocks[] = new Block(
                            startLine: $lastLine,
                            endLine: $token->line,
                            namespace: $namespace,
                            imports: $imports,
                        );
                        $lastLine = $token->line + 1;
                    }

                    $namespace = $this->parseNamespace();
                    break;
            }

            $lastToken = $token;
        }

        if ($lastToken && ($namespace !== null || !empty($imports))) {
            $blocks[] = new Block(
                startLine: $lastLine,
                endLine: $lastToken->line,
                namespace: $namespace ?? '',
                imports: $imports,
            );
        }

        return new File($blocks);
    }

    private function next(): ?PhpToken
    {
        for ($i = $this->pointer; $i < $this->numTokens; $i++) {
            $this->pointer++;

            if (!$this->tokens[$i]->isIgnorable()) {
                return $this->tokens[$i];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function parseUseStatement(): array
    {
        $groupRoot = '';
        $class = '';
        $alias = '';
        $statements = [];
        $explicitAlias = false;

        while ($token = $this->next()) {
            switch (true) {
                case !$explicitAlias && $token->is(T_STRING):
                    $class = $alias =  $token->text;
                    break;

                case $explicitAlias && $token->is(T_STRING):
                    $alias = $token->text;
                    break;

                case $token->is([T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED]):
                    $class = $token->text;
                    $classSplit = explode('\\', $token->text);
                    $alias = $classSplit[count($classSplit) - 1];
                    break;

                case $token->is(T_NS_SEPARATOR):
                    $class .= '\\';
                    $alias = '';
                    break;

                case $token->is(T_AS):
                    $explicitAlias = true;
                    $alias = '';
                    break;

                case $token->text === ',':
                    $statements[strtolower($alias)] = $groupRoot . $class;
                    $class = $alias = '';
                    $explicitAlias = false;
                    break;

                case $token->text === '{':
                    $groupRoot = $class;
                    $class = '';
                    break;

                case $token->text === '}':
                    break;

                case $token->text === ';':
                    if ($alias !== '') {
                        $statements[strtolower($alias)] = $groupRoot . $class;
                    }
                    break(2);
            }
        }

        return $statements;
    }

    private function parseNamespace(): string
    {
        $namespace = '';
        while ($token = $this->next()) {
            if ($token->text === ';') {
                return $namespace;
            }

            $namespace .= trim($token->text);
        }

        // @codeCoverageIgnoreStart
        throw new LogicException('Namespace not found.');
        // @codeCoverageIgnoreEnd
    }
}
