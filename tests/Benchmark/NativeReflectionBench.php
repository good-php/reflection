<?php

namespace Tests\Benchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
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

class NativeReflectionBench extends ReflectionBench
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

	#[Iterations(ReflectionBench::ITERATIONS_WITH_CACHE)]
	#[Revs(ReflectionBench::REVS_WITH_CACHE)]
	#[Warmup(1)]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_WARM_CACHE])]
	public function benchWarm(array $params): void
	{
		$this->callMethods($params['scope'], new ReflectionClass(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[BeforeMethods('setUp')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_COLD_CACHE])]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['scope'], new ReflectionClass(ClassStub::class));
	}

	private function callMethods(string $scope, ReflectionClass $reflection): void
	{
		$reflection->getFileName();
		$reflection->getName();

		if ($scope === 'everything') {
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
