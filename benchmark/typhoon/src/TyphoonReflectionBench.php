<?php

use Illuminate\Support\Str;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Tests\Stubs\Classes\ClassStub;
use Typhoon\OPcache\TyphoonOPcache;
use Typhoon\Reflection\Cache\FreshCache;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\TyphoonReflector;

class TyphoonReflectionBench
{
	private TyphoonReflector $reflector;

	public function setUpWithMemoryCache(): void
	{
		$this->reflector = TyphoonReflector::build();
	}

	public function setUpWithFileCache(): void
	{
		$freshOpcache = new FreshCache(new TyphoonOPcache(sys_get_temp_dir() . '/' . Str::random(32)));
		$freshOpcache->clear();

		$this->reflector = TyphoonReflector::build(cache: $freshOpcache);
	}

	public function setUpWithoutCache(): void
	{
		$this->reflector = TyphoonReflector::build(cache: new Psr16Cache(new NullAdapter()));
	}

	#[Iterations(50)]
	#[Revs(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithMemoryCache')]
	#[ParamProviders('hardnessProvider')]
	public function benchWarmWithMemoryCache(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(50)]
	#[Revs(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithFileCache')]
	#[ParamProviders('hardnessProvider')]
	public function benchWarmWithFileCache(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(200)]
	#[Warmup(1)]
	#[BeforeMethods('setUpWithoutCache')]
	#[ParamProviders('hardnessProvider')]
	public function benchCold(array $params): void
	{
		$this->callMethods($params['hardness'], $this->reflector->reflectClass(ClassStub::class));
	}

	#[Iterations(200)]
	#[ParamProviders('hardnessProvider')]
	public function benchColdIncludingInitializationAndAutoLoad(array $params): void
	{
		$this->setUpWithoutCache();

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

	private function callMethods(array $hardness, ClassReflection $reflection): void
	{
		if ($hardness['name']) {
			$reflection->file();
			(string) $reflection->id;
		}

		if ($hardness['everything']) {
			$reflection->attributes();
			$reflection->templates();
			$reflection->parentName();
			//			$reflection->implements();
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
