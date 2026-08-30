<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Extractor as GenericsExtractor;

class ReflectorScopeResolver
{
	public function __construct(
		/** @readonly */
		private GenericsExtractor $genericsExtractor,
	) {}

	/**
	 * @throws \ReflectionException
	 */
	public function resolve(\ReflectionAttribute|\Reflector $reflector): Scope
	{
		switch (true) {
			case $reflector instanceof \ReflectionClass:
				return new Scope(
					file: ($file = $reflector->getFileName()) === false ? null : $file,
					line: ($line = $reflector->getStartLine()) === false ? null : $line,
					class: $reflector->getName(),
					comment: (string)$reflector->getDocComment(),
					genericsResolver: new GenericsResolver\GenericTypeMap(),
				);

			case $reflector instanceof \ReflectionMethod:
				return new Scope(
					file: ($file = $reflector->getFileName()) === false ? null : $file,
					line: ($line = $reflector->getStartLine()) === false ? null : $line,
					class: ($class = $reflector->getDeclaringClass())->getName(),
					comment: (string)$reflector->getDocComment(),
					genericsResolver: $this->genericsExtractor->extractFromReflector($class),
				);

			case $reflector instanceof \ReflectionFunction:
				return new Scope(
					file: ($file = $reflector->getFileName()) === false ? null : $file,
					line: ($line = $reflector->getStartLine()) === false ? null : $line,
					class: $reflector->getClosureScopeClass()?->getName(),
					comment: (string)$reflector->getDocComment(),
					genericsResolver: new GenericsResolver\GenericTypeMap(),
				);

			case $reflector instanceof \ReflectionClassConstant:
				$class = $reflector->getDeclaringClass();

				return new Scope(
					file: ($file = $class->getFileName()) === false ? null : $file,
					line: ($line = $class->getStartLine()) === false ? null : $line,
					class: $class->getName(),
					comment: (string)$reflector->getDocComment(),
					genericsResolver: $this->genericsExtractor->extractFromReflector($class),
				);

			case $reflector instanceof \ReflectionProperty:
				$class = $reflector->getDeclaringClass();

				return new Scope(
					file: ($file = $class->getFileName()) === false ? null : $file,
					line: ($line = $class->getStartLine()) === false ? null : $line,
					class: $class->getName(),
					comment: (string)$reflector->getDocComment(),
					genericsResolver: $this->genericsExtractor->extractFromReflector($class),
				);

			default:
				return throw new \InvalidArgumentException('Cannot determine scope information for reflector of type ' . \get_class($reflector));
		}
	}
}
