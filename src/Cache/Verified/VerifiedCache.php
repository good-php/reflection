<?php

namespace GoodPhp\Reflection\Cache\Verified;

use Psr\SimpleCache\CacheInterface;

final class VerifiedCache
{
	public function __construct(
		private readonly CacheInterface $cacheStorage,
	) {}

	/**
	 * @template ItemType
	 *
	 * @param callable(ItemType): ?string $verificationKey
	 * @param callable(): ?ItemType       $delegate
	 *
	 * @return ItemType|null
	 */
	public function remember(string $key, callable $verificationKey, callable $delegate): mixed
	{
		if ($cacheItem = $this->cacheStorage->get($key)) {
			/** @var CacheItem<ItemType> $cacheItem */
			if ($cacheItem->verificationKey !== $verificationKey($cacheItem->value)) {
				$this->cacheStorage->delete($key);

				return $this->remember($key, $verificationKey, $delegate);
			}

			return $cacheItem->value;
		}

		$item = $delegate();

		if (!$item) {
			return $item;
		}

		$itemVerificationKey = $verificationKey($item);

		if (!$itemVerificationKey) {
			return $item;
		}

		$this->cacheStorage->set($key, new CacheItem(
			value: $item,
			verificationKey: $itemVerificationKey
		));

		return $item;
	}
}
