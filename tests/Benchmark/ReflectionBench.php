<?php

namespace Tests\Benchmark;

abstract class ReflectionBench
{
//	public const ITERATIONS_WITH_CACHE = 50;
//	public const REVS_WITH_CACHE = 200;
//
//	public const ITERATIONS_WITHOUT_CACHE = 200;

	public const ITERATIONS_WITH_CACHE = 2;
	public const REVS_WITH_CACHE = 2;

	public const ITERATIONS_WITHOUT_CACHE = 2;

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
