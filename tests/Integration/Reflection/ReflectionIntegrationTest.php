<?php

namespace Tests\Integration\Reflection;

use DateTime;
use GoodPhp\Reflection\Reflector\Reflection\ClassReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use stdClass;
use Tests\Integration\IntegrationTestCase;
use Tests\Stubs\AttributeStub;
use Tests\Stubs\Classes\ClassStub;
use Tests\Stubs\Classes\DoubleTemplateType;
use Tests\Stubs\Classes\ParentClassStub;
use Tests\Stubs\Classes\SomeStub;
use Tests\Stubs\Interfaces\ParentInterfaceStub;
use Tests\Stubs\Traits\ParentTraitStub;

class ReflectionIntegrationTest extends IntegrationTestCase
{
	public static function reflectsNamedTypeProvider(): iterable
	{
		yield [
			NamedType::wrap(ClassStub::class, [stdClass::class]),
			function (ClassReflection $reflection) {
				self::assertSame(realpath(__DIR__ . '/../../Stubs/Classes/ClassStub.php'), $reflection->fileName());
				self::assertSame(ClassStub::class, $reflection->qualifiedName());
				self::assertEquals(new Collection([new AttributeStub('123')]), $reflection->attributes()->all());

				with($reflection->typeParameters(), function (Collection $parameters) {
					self::assertCount(2, $parameters);

					self::assertEquals('T', $parameters[0]->name);
					self::assertFalse($parameters[0]->variadic);
					self::assertNull($parameters[0]->upperBound);
					self::assertSame(TemplateTypeVariance::INVARIANT, $parameters[0]->variance);

					self::assertEquals('S', $parameters[1]->name);
					self::assertFalse($parameters[1]->variadic);
					self::assertEquals(PrimitiveType::integer(), $parameters[1]->upperBound);
					self::assertSame(TemplateTypeVariance::COVARIANT, $parameters[1]->variance);
				});

				self::assertEquals(
					NamedType::wrap(ParentClassStub::class, [stdClass::class, SomeStub::class]),
					$reflection->extends()
				);

				self::assertCount(1, $reflection->implements());
				self::assertEquals(
					NamedType::wrap(ParentInterfaceStub::class, [new TemplateType('S'), SomeStub::class]),
					$reflection->implements()[0]
				);

				self::assertCount(1, $reflection->uses());
				self::assertEquals(new NamedType(ParentTraitStub::class), $reflection->uses()[0]);

				with($reflection->properties(), function (Collection $properties) use ($reflection) {
					self::assertCount(5, $properties);

					self::assertSame('parentProperty', $properties[0]->name());
					self::assertEquals(new NamedType(stdClass::class), $properties[0]->type());
					self::assertTrue($properties[0]->hasDefaultValue());
					self::assertNull($properties[0]->defaultValue());
					self::assertFalse($properties[0]->isPromoted());
					self::assertNull($properties[0]->promotedParameter());
					self::assertEmpty($properties[0]->attributes()->all());

					self::assertSame('prop', $properties[1]->name());
					self::assertEquals(PrimitiveType::integer(), $properties[1]->type());
					self::assertFalse($properties[1]->hasDefaultValue());
					self::assertFalse($properties[1]->isPromoted());
					self::assertNull($properties[1]->promotedParameter());
					self::assertEmpty($properties[1]->attributes()->all());

					self::assertSame('factories', $properties[2]->name());
					self::assertEquals(PrimitiveType::array(SomeStub::class), $properties[2]->type());
					self::assertFalse($properties[2]->hasDefaultValue());
					self::assertFalse($properties[2]->isPromoted());
					self::assertNull($properties[2]->promotedParameter());
					self::assertEquals(new Collection([new AttributeStub('4')]), $properties[2]->attributes()->all());

					self::assertSame('generic', $properties[3]->name());
					self::assertEquals(NamedType::wrap(DoubleTemplateType::class, [DateTime::class, stdClass::class]), $properties[3]->type());
					self::assertFalse($properties[3]->hasDefaultValue());
					self::assertFalse($properties[3]->isPromoted());
					self::assertNull($properties[3]->promotedParameter());
					self::assertEmpty($properties[3]->attributes()->all());

					self::assertSame('promoted', $properties[4]->name());
					self::assertEquals(NamedType::wrap(stdClass::class), $properties[4]->type());
					self::assertFalse($properties[4]->hasDefaultValue());
					self::assertTrue($properties[4]->isPromoted());
					self::assertSame($reflection->constructor()->parameters()[0], $properties[4]->promotedParameter());
					self::assertEquals(new Collection([new AttributeStub('6')]), $properties[4]->attributes()->all());
				});

				with($reflection->methods(), function (Collection $methods) use ($reflection) {
					self::assertCount(6, $methods);

					self::assertSame('parentMethod', $methods[0]->name());
					self::assertSame('traitMethod', $methods[1]->name());
					self::assertSame('method', $methods[2]->name());
					self::assertSame('methodTwo', $methods[3]->name());
					self::assertSame('self', $methods[4]->name());
					self::assertSame('par', $methods[5]->name());
				});
			},
		];
	}

	/**
	 * @dataProvider reflectsNamedTypeProvider
	 */
	public function testReflectsNamedType(NamedType|string $type, callable $assertReflection): void
	{
		if (is_string($type)) {
			$type = new NamedType($type);
		}

		$actual = $this->reflector->forNamedType($type);

		$assertReflection($actual);
	}
}
