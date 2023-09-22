<?php

namespace Tests\Benchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\Stubs\AttributeStub;
use Tests\Stubs\Classes\ClassStub;

class NativeReflectionBench
{
	public function setUp(): void
	{
		// These imitate a #[Warmup(1)] from other cold benchmark by triggering autoload manually.
		class_exists(ClassStub::class);
		class_exists(AttributeStub::class);
		class_exists(ReflectionClass::class);
		class_exists(ReflectionAttribute::class);
		class_exists(ReflectionProperty::class);
		class_exists(ReflectionMethod::class);
	}

	#[Iterations(50)]
	#[Revs(200)]
	#[Warmup(1)]
	#[ParamProviders('hardnessProvider')]
	public function benchWarm(array $params): void
	{
		$this->callMethods($params['hardness'], new ReflectionClass(ClassStub::class));
	}

	#[Iterations(200)]
	#[BeforeMethods('setUp')]
	#[ParamProviders('hardnessProvider')]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['hardness'], new ReflectionClass(ClassStub::class));
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
			array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $reflection->getAttributes());
			$reflection->getParentClass();
			$reflection->getInterfaceNames();
			$reflection->getTraitNames();
			$reflection->getProperties();
			$reflection->getMethods();
			$reflection->isAnonymous();
			$reflection->isAbstract();
			$reflection->isFinal();
			$reflection->isUserDefined();
		}
	}
}
