<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\Factory as GenericsResolverFactory;

class ReflectorScopeResolver
{
	public function __construct(
		/** @readonly */
		private GenericsResolverFactory $genericsResolverFactory,
	) {
		//
	}

	public function resolve(Reflector|ReflectionAttribute $reflector): Scope
	{
		return match (true) {
			$reflector instanceof ReflectionClass
			=> new Scope(
				file: ($file = $reflector->getFileName()) === false ? null : $file,
				line: ($line = $reflector->getStartLine()) === false ? null : $line,
				class: $reflector->getName(),
				comment: (string)$reflector->getDocComment(),
				genericsResolver: new GenericsResolver\Resolver(),
			),

			$reflector instanceof ReflectionMethod && ($class = $reflector->getDeclaringClass())
			=> new Scope(
				file: ($file = $reflector->getFileName()) === false ? null : $file,
				line: ($line = $reflector->getStartLine()) === false ? null : $line,
				class: $class->getName(),
				comment: (string)$reflector->getDocComment(),
				genericsResolver: $this->genericsResolverFactory->extractFromReflector($class),
			),

			$reflector instanceof ReflectionFunction
			=> new Scope(
				file: ($file = $reflector->getFileName()) === false ? null : $file,
				line: ($line = $reflector->getStartLine()) === false ? null : $line,
				class: ($class = $reflector->getClosureScopeClass()) ? $class->getName() : null,
				comment: (string)$reflector->getDocComment(),
				genericsResolver: new GenericsResolver\Resolver(),
			),

			$reflector instanceof ReflectionClassConstant && ($class = $reflector->getDeclaringClass())
			=> new Scope(
				file: ($file = $class->getFileName()) === false ? null : $file,
				line: ($line = $class->getStartLine()) === false ? null : $line,
				class: $class->getName(),
				comment: (string)$reflector->getDocComment(),
				genericsResolver: $this->genericsResolverFactory->extractFromReflector($class),
			),

			$reflector instanceof ReflectionProperty && ($class = $reflector->getDeclaringClass())
			=> new Scope(
				file: ($file = $class->getFileName()) === false ? null : $file,
				line: ($line = $class->getStartLine()) === false ? null : $line,
				class: $class->getName(),
				comment: (string)$reflector->getDocComment(),
				genericsResolver: $this->genericsResolverFactory->extractFromReflector($class),
			),

			default
			=> throw new InvalidArgumentException('Cannot determine scope information for reflector of type ' . get_class($reflector)),
		};
	}
}
