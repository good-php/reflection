<?php

namespace Benchmark\Typhoon;

use Benchmark\ReflectionBench;
use Benchmark\Stubs\Classes\ClassStub;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Typhoon\OPcache\TyphoonOPcache;
use Typhoon\Reflection\Cache\FreshCache;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\TyphoonReflector;

class TyphoonReflectionBench extends ReflectionBench
{
	private TyphoonReflector $reflector;

	public function setUpWithMemoryCache(): void
	{
		$this->reflector = TyphoonReflector::build();
	}

	public function setUpWithFileCache(): void
	{
		$freshOpcache = new FreshCache(new TyphoonOPcache(sys_get_temp_dir() . '/' . bin2hex(openssl_random_pseudo_bytes(10))));
		$freshOpcache->clear();

		$this->reflector = TyphoonReflector::build(cache: $freshOpcache);
	}

	public function setUpWithoutCache(): void
	{
		$this->reflector = TyphoonReflector::build(cache: new Psr16Cache(new NullAdapter()));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITH_CACHE)]
	#[Revs(ReflectionBench::REVS_WITH_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithMemoryCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_MEMORY_CACHE])]
	public function benchWarmWithMemoryCache(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITH_CACHE)]
	#[Revs(ReflectionBench::REVS_WITH_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithFileCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_FILE_CACHE])]
	public function benchWarmWithFileCache(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithoutCache')]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_NO_CACHE])]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(ReflectionBench::ITERATIONS_WITHOUT_CACHE)]
	#[ParamProviders('scopeProvider')]
	#[Groups([ReflectionBench::GROUP_NO_CACHE, ReflectionBench::GROUP_INITIALIZATION])]
	public function benchColdIncludingInitializationAndAutoLoad(array $params): void
	{
		$this->setUpWithoutCache();

		$this->callMethods($params['scope'], $this->reflector->reflectClass(ClassStub::class));
	}

	private function callMethods(string $scope, ClassReflection $reflection): void
	{
		$reflection->file();
		(string) $reflection->id;

		if ($scope === 'everything') {
			$reflection->attributes();
			$reflection->templates();
			$reflection->parentName();
			//						$reflection->implements();
			//			$reflection->uses();
			$reflection->properties();
			$reflection->methods();
			//			$reflection->isAnonymous();
			$reflection->isAbstract();
			$reflection->isFinal();
			//			$reflection->isBuiltIn();
		}
	}
}
