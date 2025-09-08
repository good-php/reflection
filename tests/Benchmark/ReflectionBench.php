<?php

namespace Tests\Benchmark;

abstract class ReflectionBench
{
	public const ITERATIONS_WITH_CACHE = 10;
	public const REVS_WITH_CACHE = 100;

	public const ITERATIONS_WITHOUT_CACHE = 100;

	public const GROUP_INITIALIZATION = 'initialization';
	public const GROUP_COLD_CACHE = 'cold_cache';
	public const GROUP_WARM_CACHE = 'warm_cache';

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
