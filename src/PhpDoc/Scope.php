<?php

declare(strict_types=1);

namespace uuf6429\PHPStanPHPDocTypeResolver\PhpDoc;

use uuf6429\PHPStanPHPDocTypeResolver\PhpDoc\GenericsResolver\GenericTypeMap;

class Scope
{
	/**
	 * PHPDoc Scope.
	 *
	 * Represents a collection of information at the location where a PHPDoc comment occurred:
	 * - File & (approximate)line - for handling namespacing correctly (especially files with multiple namespaces).
	 * - Class - (must fully-qualified!) is for resolving $this, self etc.
	 * - Comment - the PHPDoc comment block.
	 * - Inherited Generic Types - given that the scope, for example, represents a method, this is a list of generics
	 *   from the class-level PHPDoc, if any (and not generic types from the current $comment).
	 *
	 * Note: while every field reference is `@readonly`, {@see $genericsResolver} is itself a mutable collaborator
	 * (it accumulates template-type mappings during resolution), so a `Scope` is not deeply immutable.
	 *
	 * @param null|class-string $class
	 */
	public function __construct(
		/** @readonly */
		public ?string $file,
		/** @readonly */
		public ?int $line,
		/** @readonly */
		public ?string $class,
		/** @readonly */
		public string $comment,
		/** @readonly */
		public GenericTypeMap $genericsResolver,
	) {}
}
