<?php

namespace Tests\Benchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Tests\Stubs\Classes\ClassStub;

class BetterReflectionBench extends ReflectionBench
{
	private Reflector $reflector;

	public function setUpWithMemoryCache(): void
	{
		$this->reflector = (new BetterReflection())->reflector();
	}

	public function setUpWithoutCache(): void
	{
		$builder = new BetterReflection();

		// There's no simple way of disabling in-memory cache with BetterReflection,
		// but it's useful for these benchmarks.
		(
			fn () => $this->sourceLocator =
			(fn () => $this->wrappedSourceLocator)->call($this->sourceLocator())
		)->call($builder);

		$this->reflector = $builder->reflector();
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITH_CACHE)]
	#[Revs(ReflectionBench::REVS_WITH_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithMemoryCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_WARM_CACHE])]
	public function benchWarmWithMemoryCache(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithoutCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_COLD_CACHE])]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_COLD_CACHE, ReflectionBench::GROUP_INITIALIZATION])]
	public function benchColdIncludingInitializationAndAutoLoad(array $params): void
	{
		$this->setUpWithoutCache();

		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	private function callMethods(string $scope, ReflectionClass $reflection): void
	{
		$reflection->getFileName();
		$reflection->getName();

		if ($scope === 'everything') {
			array_map(fn (ReflectionAttribute $attribute) => new ($attribute->getName())(...$attribute->getArguments()), $reflection->getAttributes());
			$reflection->getParentClassName();
			$reflection->getInterfaceClassNames();
			$reflection->getTraitNames();
			$reflection->getImmediateProperties();
			$reflection->getImmediateMethods();
			$reflection->isAnonymous();
			$reflection->isAbstract();
			$reflection->isFinal();
			$reflection->isUserDefined();
		}
	}
}
