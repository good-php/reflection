<?php

namespace Tests\Unit\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use GoodPhp\Reflection\Reflection\SpecialTypeReflection;
use GoodPhp\Reflection\ReflectorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ReflectorBuilder::class)]
class ReflectorBuilderTest extends TestCase
{
	public function testBuildsWithAddedReflectionProvider(): void
	{
		$reflector = (new ReflectorBuilder())
			->withMemoryCache()
			->withAddedFallbackDefinitionProvider(new class () implements DefinitionProvider {
				public function forType(string $type): ?TypeDefinition
				{
					if ($type === 'test') {
						throw new RuntimeException('My provider');
					}

					return null;
				}
			})
			->build();

		self::assertInstanceOf(SpecialTypeReflection::class, $reflector->forType('string'));

		$this->expectExceptionObject(new RuntimeException('My provider'));
		$reflector->forType('test');
	}
}
