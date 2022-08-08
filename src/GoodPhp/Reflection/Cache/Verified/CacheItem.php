<?php

namespace GoodPhp\Reflection\Cache\Verified;

/**
 * @template-covariant ValueType
 */
final class CacheItem
{
	/**
	 * @param ValueType $value
	 */
	public function __construct(
		public readonly mixed $value,
		public readonly string $verificationKey,
	) {
	}
}
