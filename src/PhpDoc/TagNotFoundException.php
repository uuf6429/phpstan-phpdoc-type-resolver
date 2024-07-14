<?php

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use RuntimeException;

class TagNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $tagName,
    ) {
        parent::__construct("The `$this->tagName` tag was not defined");
    }
}
