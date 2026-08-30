<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver;

interface ResolverStateInterface
{
	public function isConcrete(): bool;

	public function setConcrete(bool $enabled): void;
}
