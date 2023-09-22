<?php

namespace Tests\Benchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Tests\Stubs\Classes\ClassStub;

class BetterReflectionBench
{
	private Reflector $reflector;

	public function setUp(): void
	{
		$this->reflector = (new BetterReflection())->reflector();
	}

	#[Iterations(50)]
	#[Revs(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUp')]
	#[ParamProviders('hardnessProvider')]
	public function benchWarm(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUp')]
	#[ParamProviders('hardnessProvider')]
	public function benchColdExceptInitialization(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(200)]
	#[ParamProviders('hardnessProvider')]
	public function benchColdIncludingInitializationAndAutoLoad(array $params): void
	{
		$this->setUp();

		$this->callMethods($params['hardness'], $this->reflector->reflectClass(ClassStub::class));
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

	private function callMethods(array $hardness, ReflectionClass $reflection): void
	{
		if ($hardness['name']) {
			$reflection->getFileName();
			$reflection->getName();
		}

		if ($hardness['everything']) {
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
