<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use RuntimeException;

class TagNotFoundException extends RuntimeException
{
	public function __construct(
		/** @readonly */
		public string $tagName,
	) {
		parent::__construct("The `$this->tagName` tag was not defined");
	}
}
