<?php

namespace GoodPhp\Reflection\Cache\Verified\Storage;

interface CacheStorage
{
	public function get(string $key): mixed;

	public function set(string $key, mixed $data): void;

	public function remove(string $key): void;
}
