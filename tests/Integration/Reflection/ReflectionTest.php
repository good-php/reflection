<?php

namespace Tests\Integration\Reflection;

use ArrayAccess;
use DateTime;
use Generator;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\StaticType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use stdClass;
use Tests\Integration\IntegrationTestCase;
use Tests\Stubs\AttributeStub;
use Tests\Stubs\Classes\ClassStub;
use Tests\Stubs\Classes\CollectionStub;
use Tests\Stubs\Classes\DoubleTemplateType;
use Tests\Stubs\Classes\ParentClassStub;
use Tests\Stubs\Classes\SomeStub;
use Tests\Stubs\Interfaces\ParentInterfaceStub;
use Tests\Stubs\Interfaces\SingleTemplateType;
use Tests\Stubs\Traits\ParentTraitStub;
use Traversable;

class ReflectionTest extends IntegrationTestCase
{
	public static function reflectsNamedTypeProvider(): iterable
	{
		yield 'ClassStub<stdClass>' => [
			NamedType::wrap(ClassStub::class, [stdClass::class]),
			function (ClassReflection $reflection) {
				self::assertEquals(NamedType::wrap(ClassStub::class, [stdClass::class]), $reflection->type());
				self::assertSame(realpath(__DIR__ . '/../../Stubs/Classes/ClassStub.php'), $reflection->fileName());
				self::assertSame(ClassStub::class, $reflection->qualifiedName());
				self::assertSame('ClassStub', $reflection->shortName());
				self::assertSame(ClassStub::class, $reflection->location());
				self::assertSame('ClassStub', (string) $reflection);
				self::assertFalse($reflection->isAnonymous());
				self::assertFalse($reflection->isAbstract());
				self::assertTrue($reflection->isFinal());
				self::assertFalse($reflection->isBuiltIn());
				self::assertEquals([new AttributeStub('123')], $reflection->attributes()->all());
				self::assertTrue($reflection->attributes()->has());
				self::assertEquals('#[\Tests\Stubs\AttributeStub(...)]', (string) $reflection->attributes());

				with($reflection->typeParameters(), function (array $parameters) use ($reflection) {
					self::assertCount(2, $parameters);
					self::assertContainsOnlyInstancesOf(TypeParameterReflection::class, $parameters);

					self::assertEquals('T', $parameters[0]->name());
					self::assertFalse($parameters[0]->variadic());
					self::assertSame(MixedType::get(), $parameters[0]->upperBound());
					self::assertSame(TemplateTypeVariance::INVARIANT, $parameters[0]->variance());

					self::assertSame($parameters[0], $reflection->typeParameter('T'));
					self::assertSame($parameters[0], $reflection->typeParameter(0));
					self::assertEquals('S', $parameters[1]->name());
					self::assertFalse($parameters[1]->variadic());
					self::assertEquals(PrimitiveType::integer(), $parameters[1]->upperBound());
					self::assertSame(TemplateTypeVariance::COVARIANT, $parameters[1]->variance());
					self::assertSame($parameters[1], $reflection->typeParameter('S'));
					self::assertSame($parameters[1], $reflection->typeParameter(1));
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

				with($reflection->uses(), function (UsedTraitsReflection $uses) {
					self::assertCount(2, $uses->traits());

					with($uses->traits()[0], function (UsedTraitReflection $usedTrait) {
						self::assertEquals(
							NamedType::wrap(ParentTraitStub::class, [stdClass::class, SomeStub::class]),
							$usedTrait->trait(),
						);

						self::assertCount(2, $usedTrait->aliases());

						self::assertSame('traitMethod', $usedTrait->aliases()[0]->name());
						self::assertNull($usedTrait->aliases()[0]->newName());
						self::assertSame(ReflectionMethod::IS_PRIVATE, $usedTrait->aliases()[0]->newModifier());

						self::assertSame('traitMethod', $usedTrait->aliases()[1]->name());
						self::assertSame('traitMethodTwo', $usedTrait->aliases()[1]->newName());
						self::assertSame(ReflectionMethod::IS_PROTECTED, $usedTrait->aliases()[1]->newModifier());
					});

					with($uses->traits()[1], function (UsedTraitReflection $usedTrait) {
						self::assertEquals(NamedType::wrap(ParentTraitStub::class), $usedTrait->trait());

						self::assertEmpty($usedTrait->aliases());
					});

					self::assertCount(0, $uses->excludedTraitMethods());
				});

				with($reflection->declaredProperties(), function (array $properties) use ($reflection) {
					self::assertCount(3, $properties);
					self::assertContainsOnlyInstancesOf(PropertyReflection::class, $properties);

					self::assertSame('factories', $properties[0]->name());
					self::assertSame($reflection->properties()[0], $properties[0]);

					self::assertSame('generic', $properties[1]->name());
					self::assertSame($reflection->properties()[1], $properties[1]);

					self::assertSame('promoted', $properties[2]->name());
					self::assertSame($reflection->properties()[2], $properties[2]);
				});

				with($reflection->properties(), function (array $properties) use ($reflection) {
					self::assertCount(5, $properties);
					self::assertContainsOnlyInstancesOf(PropertyReflection::class, $properties);

					self::assertSame('factories', $properties[0]->name());
					self::assertEquals(PrimitiveType::array(SomeStub::class), $properties[0]->type());
					self::assertFalse($properties[0]->hasDefaultValue());
					self::assertFalse($properties[0]->isPromoted());
					self::assertNull($properties[0]->promotedParameter());
					self::assertEquals([new AttributeStub('4')], $properties[0]->attributes()->all());
					self::assertSame($properties[0], $reflection->property('factories'));

					self::assertSame('generic', $properties[1]->name());
					self::assertEquals(NamedType::wrap(DoubleTemplateType::class, [DateTime::class, stdClass::class]), $properties[1]->type());
					self::assertFalse($properties[1]->hasDefaultValue());
					self::assertFalse($properties[1]->isPromoted());
					self::assertNull($properties[1]->promotedParameter());
					self::assertEmpty($properties[1]->attributes()->all());
					self::assertSame($properties[1], $reflection->property('generic'));

					self::assertSame('promoted', $properties[2]->name());
					self::assertEquals(NamedType::wrap(stdClass::class), $properties[2]->type());
					self::assertFalse($properties[2]->hasDefaultValue());
					self::assertTrue($properties[2]->isPromoted());
					self::assertSame($reflection->constructor()->parameters()[0], $properties[2]->promotedParameter());
					self::assertEquals([new AttributeStub('6')], $properties[2]->attributes()->all());
					self::assertSame($properties[2], $reflection->property('promoted'));

					self::assertSame('parentProperty', $properties[3]->name());
					self::assertEquals(new NamedType(stdClass::class), $properties[3]->type());
					self::assertTrue($properties[3]->hasDefaultValue());
					self::assertNull($properties[3]->defaultValue());
					self::assertFalse($properties[3]->isPromoted());
					self::assertNull($properties[3]->promotedParameter());
					self::assertEmpty($properties[3]->attributes()->all());
					self::assertSame($properties[3], $reflection->property('parentProperty'));

					self::assertSame('prop', $properties[4]->name());
					self::assertEquals(PrimitiveType::integer(), $properties[4]->type());
					self::assertFalse($properties[4]->hasDefaultValue());
					self::assertFalse($properties[4]->isPromoted());
					self::assertNull($properties[4]->promotedParameter());
					self::assertEmpty($properties[4]->attributes()->all());
					self::assertSame($properties[4], $reflection->property('prop'));
				});

				with($reflection->declaredMethods(), function (array $methods) use ($reflection) {
					self::assertCount(4, $methods);
					self::assertContainsOnlyInstancesOf(MethodReflection::class, $methods);

					self::assertSame('__construct', $methods[0]->name());
					//					self::assertSame($reflection->methods()[4], $methods[0]);

					self::assertSame('method', $methods[1]->name());
					//					self::assertSame($reflection->methods()[5], $methods[1]);

					self::assertSame('methodTwo', $methods[2]->name());
					//					self::assertSame($reflection->methods()[6], $methods[2]);

					self::assertSame('self', $methods[3]->name());
					//					self::assertSame($reflection->methods()[7], $methods[3]);
				});

				with($reflection->methods(), function (array $methods) use ($reflection) {
					self::assertCount(9, $methods);
					self::assertContainsOnlyInstancesOf(MethodReflection::class, $methods);

					self::assertSame('__construct', $methods[0]->name());
					self::assertEmpty($methods[0]->attributes()->all());
					self::assertEmpty($methods[0]->typeParameters());
					with($methods[0]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('promoted', $parameters[0]->name());
						self::assertEquals(new NamedType(stdClass::class), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEquals([new AttributeStub('6')], $parameters[0]->attributes()->all());
						self::assertSame('arg $promoted', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[0]->parameter('promoted'));
						self::assertSame($parameters[0], $methods[0]->parameter(0));
					});
					self::assertNull($methods[0]->returnType());
					self::assertSame('__construct()', (string) $methods[0]);
					self::assertSame($methods[0], $reflection->method('__construct'));
					self::assertSame($methods[0], $reflection->constructor());

					self::assertSame('method', $methods[1]->name());
					self::assertEquals([new AttributeStub('5')], $methods[1]->attributes()->all());
					with($methods[1]->typeParameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(TypeParameterReflection::class, $parameters);

						self::assertEquals('G', $parameters[0]->name());
						self::assertFalse($parameters[0]->variadic());
						self::assertSame(MixedType::get(), $parameters[0]->upperBound());
						self::assertSame(TemplateTypeVariance::INVARIANT, $parameters[0]->variance());
						self::assertSame($parameters[0], $methods[1]->typeParameter('G'));
						self::assertSame($parameters[0], $methods[1]->typeParameter(0));
					});
					with($methods[1]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('param', $parameters[0]->name());
						self::assertEquals(NamedType::wrap(DoubleTemplateType::class, [SomeStub::class, stdClass::class]), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEquals([new AttributeStub('6')], $parameters[0]->attributes()->all());
						self::assertSame('arg $param', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[1]->parameter('param'));
						self::assertSame($parameters[0], $methods[1]->parameter(0));
					});
					self::assertEquals(NamedType::wrap(Collection::class, [new TemplateType('S'), new TemplateType('G')]), $methods[1]->returnType());
					self::assertSame('method()', (string) $methods[1]);
					self::assertSame($methods[1], $reflection->method('method'));

					self::assertSame('methodTwo', $methods[2]->name());
					self::assertEmpty($methods[2]->attributes()->all());
					with($methods[2]->typeParameters(), function (array $parameters) use ($methods) {
						self::assertCount(2, $parameters);
						self::assertContainsOnlyInstancesOf(TypeParameterReflection::class, $parameters);

						self::assertEquals('KValue', $parameters[0]->name());
						self::assertFalse($parameters[0]->variadic());
						self::assertSame(MixedType::get(), $parameters[0]->upperBound());
						self::assertSame(TemplateTypeVariance::INVARIANT, $parameters[0]->variance());
						self::assertSame($parameters[0], $methods[2]->typeParameter('KValue'));
						self::assertSame($parameters[0], $methods[2]->typeParameter(0));

						self::assertEquals('K', $parameters[1]->name());
						self::assertFalse($parameters[1]->variadic());
						self::assertEquals(NamedType::wrap(SingleTemplateType::class, [new TemplateType('KValue')]), $parameters[1]->upperBound());
						self::assertSame(TemplateTypeVariance::INVARIANT, $parameters[1]->variance());
						self::assertSame($parameters[1], $methods[2]->typeParameter('K'));
						self::assertSame($parameters[1], $methods[2]->typeParameter(1));
					});
					with($methods[2]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('param', $parameters[0]->name());
						self::assertEquals(new TemplateType('K'), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $param', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[2]->parameter('param'));
						self::assertSame($parameters[0], $methods[2]->parameter(0));
					});
					self::assertEquals(new TemplateType('KValue'), $methods[2]->returnType());
					self::assertSame('methodTwo()', (string) $methods[2]);
					self::assertSame($methods[2], $reflection->method('methodTwo'));

					self::assertSame('self', $methods[3]->name());
					self::assertEmpty($methods[3]->attributes()->all());
					self::assertEmpty($methods[3]->typeParameters());
					with($methods[3]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('parent', $parameters[0]->name());
						self::assertEquals(NamedType::wrap(ParentClassStub::class, [PrimitiveType::integer(), PrimitiveType::integer()]), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $parent', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[3]->parameter('parent'));
						self::assertSame($parameters[0], $methods[3]->parameter(0));
					});
					self::assertEquals(new StaticType(NamedType::wrap(ClassStub::class, [stdClass::class])), $methods[3]->returnType());
					self::assertSame('self()', (string) $methods[3]);
					self::assertSame($methods[3], $reflection->method('self'));

					self::assertSame('parentMethod', $methods[4]->name());
					self::assertEmpty($methods[4]->attributes()->all());
					self::assertEmpty($methods[4]->typeParameters());
					self::assertEmpty($methods[4]->parameters());
					self::assertEquals(new NamedType(SomeStub::class), $methods[4]->returnType());
					self::assertSame('parentMethod()', (string) $methods[4]);
					self::assertSame($methods[4], $reflection->method('parentMethod'));

					self::assertSame('test', $methods[5]->name());
					self::assertEmpty($methods[5]->attributes()->all());
					self::assertEmpty($methods[5]->typeParameters());
					with($methods[5]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('str', $parameters[0]->name());
						self::assertEquals(new NullableType(PrimitiveType::string()), $parameters[0]->type());
						self::assertTrue($parameters[0]->hasDefaultValue());
						self::assertNull($parameters[0]->defaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $str', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[5]->parameter('str'));
						self::assertSame($parameters[0], $methods[5]->parameter(0));
					});
					self::assertEquals(new StaticType(NamedType::wrap(ClassStub::class, [stdClass::class])), $methods[5]->returnType());
					self::assertSame('test()', (string) $methods[5]);
					self::assertSame($methods[5], $reflection->method('test'));

					self::assertSame('traitMethod', $methods[6]->name());
					self::assertEmpty($methods[6]->attributes()->all());
					self::assertEmpty($methods[6]->typeParameters());
					self::assertEmpty($methods[6]->parameters());
					self::assertEquals(VoidType::get(), $methods[6]->returnType());
					self::assertSame('traitMethod()', (string) $methods[6]);
					self::assertSame($methods[6], $reflection->method('traitMethod'));

					self::assertSame('traitMethodTwo', $methods[7]->name());
					self::assertEmpty($methods[7]->attributes()->all());
					self::assertEmpty($methods[7]->typeParameters());
					self::assertEmpty($methods[7]->parameters());
					self::assertEquals(VoidType::get(), $methods[7]->returnType());
					self::assertSame('traitMethodTwo()', (string) $methods[7]);
					self::assertSame($methods[7], $reflection->method('traitMethodTwo'));

					self::assertSame('otherFunction', $methods[8]->name());
					self::assertEmpty($methods[8]->attributes()->all());
					self::assertEmpty($methods[8]->typeParameters());
					self::assertEmpty($methods[8]->parameters());
					self::assertEquals(new NamedType(Generator::class), $methods[8]->returnType());
					self::assertSame('otherFunction()', (string) $methods[8]);
					self::assertSame($methods[8], $reflection->method('otherFunction'));
				});
			},
		];

		yield 'CollectionStub' => [
			CollectionStub::class,
			function (ClassReflection $reflection) {
				self::assertEquals(NamedType::wrap(CollectionStub::class), $reflection->type());
				self::assertSame(CollectionStub::class, $reflection->qualifiedName());
				self::assertSame('CollectionStub', $reflection->shortName());
				self::assertSame(CollectionStub::class, $reflection->location());
				self::assertSame('CollectionStub', (string) $reflection);

				self::assertFalse($reflection->isAnonymous());
				self::assertFalse($reflection->isAbstract());
				self::assertFalse($reflection->isFinal());
				self::assertFalse($reflection->isBuiltIn());

				self::assertEmpty($reflection->attributes()->all());
				self::assertFalse($reflection->attributes()->has());

				self::assertEmpty($reflection->typeParameters());
				self::assertNull($reflection->extends());

				self::assertCount(2, $reflection->implements());
				self::assertEquals(
					NamedType::wrap(IteratorAggregate::class, [
						PrimitiveType::string(),
						new NamedType(SomeStub::class),
					]),
					$reflection->implements()[0]
				);
				self::assertEquals(
					NamedType::wrap(ArrayAccess::class, [
						PrimitiveType::string(),
						new NamedType(SomeStub::class),
					]),
					$reflection->implements()[1]
				);

				self::assertEmpty($reflection->uses()->traits());
				self::assertEmpty($reflection->declaredProperties());
				self::assertEmpty($reflection->properties());

				with($reflection->declaredMethods(), function (array $methods) use ($reflection) {
					self::assertCount(5, $methods);
					self::assertContainsOnlyInstancesOf(MethodReflection::class, $methods);

					self::assertSame('getIterator', $methods[0]->name());
					self::assertSame('offsetExists', $methods[1]->name());
					self::assertSame('offsetGet', $methods[2]->name());
					self::assertSame('offsetSet', $methods[3]->name());
					self::assertSame('offsetUnset', $methods[4]->name());
				});

				with($reflection->methods(), function (array $methods) use ($reflection) {
					self::assertCount(5, $methods);
					self::assertContainsOnlyInstancesOf(MethodReflection::class, $methods);

					self::assertSame('getIterator', $methods[0]->name());
					self::assertEmpty($methods[0]->attributes()->all());
					self::assertEmpty($methods[0]->typeParameters());
					self::assertEmpty($methods[0]->parameters());
					self::assertEquals(NamedType::wrap(Traversable::class, [
						PrimitiveType::string(),
						SomeStub::class,
					]), $methods[0]->returnType());
					self::assertSame('getIterator()', (string) $methods[0]);
					self::assertSame($methods[0], $reflection->method('getIterator'));

					self::assertSame('offsetExists', $methods[1]->name());
					self::assertEmpty($methods[1]->attributes()->all());
					self::assertEmpty($methods[1]->typeParameters());
					with($methods[1]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('offset', $parameters[0]->name());
						self::assertEquals(PrimitiveType::string(), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $offset', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[1]->parameter('offset'));
						self::assertSame($parameters[0], $methods[1]->parameter(0));
					});
					self::assertEquals(PrimitiveType::boolean(), $methods[1]->returnType());
					self::assertSame('offsetExists()', (string) $methods[1]);
					self::assertSame($methods[1], $reflection->method('offsetExists'));

					self::assertSame('offsetGet', $methods[2]->name());
					self::assertEmpty($methods[2]->attributes()->all());
					self::assertEmpty($methods[2]->typeParameters());
					with($methods[2]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('offset', $parameters[0]->name());
						self::assertEquals(PrimitiveType::string(), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $offset', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[2]->parameter('offset'));
						self::assertSame($parameters[0], $methods[2]->parameter(0));
					});
					self::assertEquals(new NullableType(
						new NamedType(SomeStub::class)
					), $methods[2]->returnType());
					self::assertSame('offsetGet()', (string) $methods[2]);
					self::assertSame($methods[2], $reflection->method('offsetGet'));

					self::assertSame('offsetSet', $methods[3]->name());
					self::assertEmpty($methods[3]->attributes()->all());
					self::assertEmpty($methods[3]->typeParameters());
					with($methods[3]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(2, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('offset', $parameters[0]->name());
						self::assertEquals(new NullableType(PrimitiveType::string()), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $offset', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[3]->parameter('offset'));
						self::assertSame($parameters[0], $methods[3]->parameter(0));

						self::assertEquals('value', $parameters[1]->name());
						self::assertEquals(new NamedType(SomeStub::class), $parameters[1]->type());
						self::assertFalse($parameters[1]->hasDefaultValue());
						self::assertEmpty($parameters[1]->attributes()->all());
						self::assertSame('arg $value', (string) $parameters[1]);
						self::assertSame($parameters[1], $methods[3]->parameter('value'));
						self::assertSame($parameters[1], $methods[3]->parameter(1));
					});
					self::assertEquals(VoidType::get(), $methods[3]->returnType());
					self::assertSame('offsetSet()', (string) $methods[3]);
					self::assertSame($methods[3], $reflection->method('offsetSet'));

					self::assertSame('offsetUnset', $methods[4]->name());
					self::assertEmpty($methods[4]->attributes()->all());
					self::assertEmpty($methods[4]->typeParameters());
					with($methods[4]->parameters(), function (array $parameters) use ($methods) {
						self::assertCount(1, $parameters);
						self::assertContainsOnlyInstancesOf(FunctionParameterReflection::class, $parameters);

						self::assertEquals('offset', $parameters[0]->name());
						self::assertEquals(PrimitiveType::string(), $parameters[0]->type());
						self::assertFalse($parameters[0]->hasDefaultValue());
						self::assertEmpty($parameters[0]->attributes()->all());
						self::assertSame('arg $offset', (string) $parameters[0]);
						self::assertSame($parameters[0], $methods[4]->parameter('offset'));
						self::assertSame($parameters[0], $methods[4]->parameter(0));
					});
					self::assertEquals(VoidType::get(), $methods[4]->returnType());
					self::assertSame('offsetUnset()', (string) $methods[4]);
					self::assertSame($methods[4], $reflection->method('offsetUnset'));
				});
			},
		];
	}

	#[DataProvider('reflectsNamedTypeProvider')]
	public function testReflectsNamedType(NamedType|string $type, callable $assertReflection): void
	{
		if (is_string($type)) {
			$type = new NamedType($type);
		}

		$actual = $this->reflector->forNamedType($type);

		$assertReflection($actual);
	}

	public static function reflectsAnonymousTypeProvider(): iterable
	{
		yield 'empty class' => [
			new class () {},
			function (ClassReflection $reflection) {
				self::assertStringStartsWith('class@anonymous ' . __FILE__ . ':', $reflection->qualifiedName());

				$expectedClassName = $reflection->qualifiedName();

				self::assertEquals(NamedType::wrap($expectedClassName), $reflection->type());
				self::assertSame(__FILE__, $reflection->fileName());
				self::assertSame($expectedClassName, $reflection->qualifiedName());
				self::assertSame($expectedClassName, $reflection->shortName());
				self::assertSame($expectedClassName, $reflection->location());
				self::assertSame($expectedClassName, (string) $reflection);
				self::assertTrue($reflection->isAnonymous());
				self::assertFalse($reflection->isAbstract());
				self::assertFalse($reflection->isFinal());
				self::assertFalse($reflection->isBuiltIn());
				self::assertEmpty($reflection->attributes()->all());
				self::assertFalse($reflection->attributes()->has());
				self::assertEquals('#[]', (string) $reflection->attributes());

				self::assertEmpty($reflection->typeParameters());
				self::assertNull($reflection->extends());
				self::assertEmpty($reflection->implements());
				self::assertEmpty($reflection->uses()->traits());
				self::assertEmpty($reflection->declaredProperties());
				self::assertEmpty($reflection->properties());
				self::assertEmpty($reflection->declaredMethods());
				self::assertEmpty($reflection->methods());
			},
		];
	}

	#[DataProvider('reflectsAnonymousTypeProvider')]
	public function testReflectsAnonymousType(object $object, callable $assertReflection): void
	{
		$actual = $this->reflector->forType($object::class);

		$assertReflection($actual);
	}
}
