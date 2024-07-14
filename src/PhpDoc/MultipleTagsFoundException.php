<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use RuntimeException;

class MultipleTagsFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $tagName,
    ) {
        parent::__construct("More than one `$this->tagName` tags have been defined");
    }
}
