<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ReadmeSnippetsTest extends TestCase
{
	/**
	 * @var string
	 */
	private const README_FILE = __DIR__ . '/../../README.md';

	/**
	 * @dataProvider provideSnippetCases
	 */
	public function testSnippet(string $snippet): void
	{
		$this->expectNotToPerformAssertions();

		eval($snippet);
	}

	/**
	 * @return iterable<array{snippet: string}>
	 */
	public static function provideSnippetCases(): iterable
	{
		$content = file_get_contents(self::README_FILE);
		if (false === $content) {
			throw new \RuntimeException('File could not be read: ' . self::README_FILE);
		}

		preg_match_all('/\n```php\n(.+?)\n```\n/s', $content, $matches);

		foreach ($matches[1] as $i => $match) {
			yield "Snippet #{$i}" => ['snippet' => $match];
		}
	}
}
