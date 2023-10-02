<?php

namespace Tests\Benchmark;

use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\ReflectorBuilder;
use Illuminate\Support\Str;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Tests\Stubs\Classes\ClassStub;

class ThisReflectionBench
{
	private Reflector $reflector;

	public function setUpWithMemoryCache(): void
	{
		$this->reflector = (new ReflectorBuilder())
			->withMemoryCache()
			->build();
	}

	public function setUpWithFileCache(): void
	{
		$this->reflector = (new ReflectorBuilder())
			->withFileCache(sys_get_temp_dir() . '/' . Str::random(32))
			->build();
	}

	public function setUpWithoutCache(): void
	{
		$this->reflector = (new ReflectorBuilder())->build();
	}

	#[Iterations(50)]
	#[Revs(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithMemoryCache')]
	#[ParamProviders('hardnessProvider')]
	public function benchWarmWithMemoryCache(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->forType(ClassStub::class));
	}

	#[Iterations(50)]
	#[Revs(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithFileCache')]
	#[ParamProviders('hardnessProvider')]
	public function benchWarmWithFileCache(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->forType(ClassStub::class));
	}

	#[Iterations(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithoutCache')]
	#[ParamProviders('hardnessProvider')]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->forType(ClassStub::class));
	}

	#[Iterations(200)]
	#[ParamProviders('hardnessProvider')]
	public function benchColdIncludingInitializationAndAutoLoad(array $params): void
	{
		$this->setUpWithoutCache();

		$this->callMethods($params['hardness'], $this->reflector->forType(ClassStub::class));
	}

	public function hardnessProvider(): iterable
	{
		yield 'only name' => [
			'hardness' => ['name' => true, 'everything' => false],
		];

		yield 'everything' => [
			'hardness' => ['name' => true, 'everything' => true],
		];
	}

	private function callMethods(array $hardness, ClassReflection $reflection): void
	{
		if ($hardness['name']) {
			$reflection->fileName();
			$reflection->qualifiedName();
		}

		if ($hardness['everything']) {
			$reflection->attributes()->all();
			$reflection->typeParameters();
			$reflection->extends();
			$reflection->implements();
			$reflection->uses();
			$reflection->declaredProperties();
			$reflection->declaredMethods();
			$reflection->isAnonymous();
			$reflection->isAbstract();
			$reflection->isFinal();
			$reflection->isBuiltIn();
		}
	}
}
