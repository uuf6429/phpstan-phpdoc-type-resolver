<?php

namespace uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures;

use uuf6429\PHPStanPHPDocTypeResolverTests\Fixtures\PHP81\Number;

/**
 * @template T
 */
abstract class Payload
{
	/**
	 * @var T
	 */
	protected $data;

	/**
	 * @return Payload<Number>
	 */
	abstract public static function makeNumberPayload(Number $data): self;

	/**
	 * @param Payload<*> $data
	 * @return Payload<Payload<T>>
	 */
	abstract public static function makePayloadPayload(Payload $data): self;

	/**
	 * @return T
	 */
	abstract public function getData(): mixed;
}
