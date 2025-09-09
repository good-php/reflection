<?php

namespace Benchmark;

abstract class ReflectionBench
{
	public const ITERATIONS_WITH_CACHE = 10;
	public const REVS_WITH_CACHE = 100;

	public const ITERATIONS_WITHOUT_CACHE = 100;

	public const GROUP_INITIALIZATION = 'initialization';
	public const GROUP_MEMORY_CACHE = 'memory_cache';
	public const GROUP_FILE_CACHE = 'file_cache';
	public const GROUP_NO_CACHE = 'no_cache';

	public function scopeProvider(): iterable
	{
		yield 'only name' => [
			'scope' => 'name',
		];

		yield 'everything' => [
			'scope' => 'everything',
		];
	}
}
