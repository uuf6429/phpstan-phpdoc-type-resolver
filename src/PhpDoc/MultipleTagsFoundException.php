<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

class MultipleTagsFoundException extends \RuntimeException
{
	public function __construct(
		/** @readonly */
		public string $tagName,
	) {
		parent::__construct("More than one `{$this->tagName}` tags have been defined");
	}
}
