<?php

namespace Benchmark;

use Benchmark\Stubs\Classes\ClassStub;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\ReflectorBuilder;
use Illuminate\Support\Str;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

class GoodReflectionBench extends ReflectionBench
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

	#[Iterations(ReflectionBench::ITERATIONS_WITH_CACHE)]
	#[Revs(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithMemoryCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_MEMORY_CACHE])]
	public function benchWarmWithMemoryCache(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->forType(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITH_CACHE)]
	#[Revs(ReflectionBench::REVS_WITH_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithFileCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_FILE_CACHE])]
	public function benchWarmWithFileCache(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->forType(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithoutCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_NO_CACHE])]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->forType(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_NO_CACHE, ReflectionBench::GROUP_INITIALIZATION])]
	public function benchColdIncludingInitializationAndAutoLoad(array $params): void
	{
		$this->setUpWithoutCache();

		$this->callMethods($params['scope'], $this->reflector->forType(ClassStub::class));
	}

	private function callMethods(string $scope, ClassReflection $reflection): void
	{
		$reflection->fileName();
		$reflection->qualifiedName();

		if ($scope === 'everything') {
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
