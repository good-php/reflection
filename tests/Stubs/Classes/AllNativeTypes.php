<?php

namespace Tests\Stubs\Classes;

class AllNativeTypes
{
	public function f1(
		int $p1,
		string $p2,
		float $p3,
		bool $p4,
		array $p5,
		object $p6,
		callable $p7,
		iterable $p8,
		mixed $p9,
	): void {
	}

	public function f2(
		int|null $p1,
		string|float|null $p2,
		string|float $p3,
		string|false $p4,
		self $p5,
	): never {
	}

	public function f3(): static
	{
	}
}
