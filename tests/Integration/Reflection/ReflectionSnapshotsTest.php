<?php

namespace Tests\Integration\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\CacheUtils;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\SpecialTypeReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitAliasReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Str;
use Kcs\ClassFinder\Finder\ComposerFinder;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;
use Tests\Integration\IntegrationTestCase;
use Webmozart\Assert\Assert;

class ReflectionSnapshotsTest extends IntegrationTestCase
{
	public static function reflectsTypesProvider(): iterable
	{
		$namespace = 'Tests\Stubs';
		$reflections = (new ComposerFinder())->inNamespace($namespace);

		foreach ($reflections as $reflection) {
			/** @var ReflectionClass<object> $reflection */
			$className = $reflection->getName();
			$shortName = Str::after($className, "{$namespace}\\");

			yield $shortName => [
				$className,
				__DIR__ . '/ReflectionSnapshots/' . CacheUtils::normalizeTypeName($className) . '.yaml',
			];
		}
	}

	#[DataProvider('reflectsTypesProvider')]
	public function testReflectsTypes(string $className, string $snapshotFilename): void
	{
		$reflection = $this->reflector->forType($className);

		Assert::notInstanceOf($reflection, SpecialTypeReflection::class);

		$expected = [
			// Basic info
			'type'          => self::typeToExpectation($reflection->type()),
			'qualifiedName' => $reflection->qualifiedName(),
			'shortName'     => $reflection->shortName(),
			'location'      => $reflection->location(),
			'asString'      => (string) $reflection,

			'isBuiltIn' => $reflection->isBuiltIn(),
		];

		if ($reflection instanceof ClassReflection) {
			$expected = [
				...$expected,
				'isAnonymous' => $reflection->isAnonymous(),
				'isAbstract'  => $reflection->isAbstract(),
				'isFinal'     => $reflection->isFinal(),
			];
		}

		$expected = [
			...$expected,
			'attributes' => self::attributesToExpectation($reflection->attributes()),
		];

		if ($reflection instanceof HasTypeParameters) {
			$expected = [
				...$expected,
				'typeParameters' => array_map(self::typeParameterToExpectation(...), $reflection->typeParameters()),
			];
		}

		if ($reflection instanceof ClassReflection) {
			$expected = [
				...$expected,
				'extends'    => self::typeToExpectation($reflection->extends()),
				'implements' => array_map(self::typeToExpectation(...), $reflection->implements()),
				'uses'       => self::usedTraitsToExpectation($reflection->uses()),
			];
		}

		if ($reflection instanceof InterfaceReflection) {
			$expected = [
				...$expected,
				'extends' => array_map(self::typeToExpectation(...), $reflection->extends()),
			];
		}

		if ($reflection instanceof TraitReflection) {
			$expected = [
				...$expected,
				'uses' => self::usedTraitsToExpectation($reflection->uses()),
			];
		}

		if ($reflection instanceof EnumReflection) {
			$expected = [
				...$expected,
				'implements' => array_map(self::typeToExpectation(...), $reflection->implements()),
				'uses'       => self::usedTraitsToExpectation($reflection->uses()),
			];
		}

		if ($reflection instanceof HasProperties) {
			$expected = [
				...$expected,
				'declaredProperties' => array_map(self::propertyToExpectation(...), $reflection->declaredProperties()),
				'properties'         => array_map(self::propertyToExpectation(...), $reflection->properties()),
			];
		}

		if ($reflection instanceof HasMethods) {
			$expected = [
				...$expected,
				'declaredMethods' => array_map(self::methodToExpectation(...), $reflection->declaredMethods()),
				'methods'         => array_map(self::methodToExpectation(...), $reflection->methods()),
			];
		}

		$expectedString = Yaml::dump($expected, inline: 10, flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

		if (file_exists($snapshotFilename)) {
			self::assertStringEqualsFile($snapshotFilename, $expectedString);
		} else {
			\Safe\file_put_contents($snapshotFilename, $expectedString);
		}
	}

	private static function typeToExpectation(?Type $type): ?string
	{
		return $type ? (string) $type : null;
	}

	private static function typeParameterToExpectation(TypeParameterReflection $typeParameter): array
	{
		return [
			'name'       => $typeParameter->name(),
			'variadic'   => $typeParameter->variadic(),
			'upperBound' => self::typeToExpectation($typeParameter->upperBound()),
			'variance'   => $typeParameter->variance()->name,
			'asString'   => (string) $typeParameter,
		];
	}

	private static function attributesToExpectation(Attributes $attributes): array
	{
		return [
			'asString' => (string) $attributes,
			'all'      => array_map(fn (object $attribute) => serialize($attribute), $attributes->all()),
		];
	}

	private static function usedTraitsToExpectation(UsedTraitsReflection $uses): array
	{
		return [
			'traits' => array_map(fn (UsedTraitReflection $usedTrait) => [
				'trait'   => self::typeToExpectation($usedTrait->trait()),
				'aliases' => array_map(fn (UsedTraitAliasReflection $alias) => [
					'name'        => $alias->name(),
					'newName'     => $alias->newName(),
					'newModifier' => $alias->newModifier(),
				], $usedTrait->aliases()),
			], $uses->traits()),
			'excludedTraitMethods' => $uses->excludedTraitMethods(),
		];
	}

	private static function propertyToExpectation(PropertyReflection $property): array
	{
		return [
			'asString'          => (string) $property,
			'name'              => $property->name(),
			'location'          => $property->location(),
			'declaringType'     => self::typeToExpectation($property->declaringType()->type()),
			'attributes'        => self::attributesToExpectation($property->attributes()),
			'type'              => self::typeToExpectation($property->type()),
			'hasDefaultValue'   => $hasDefault = $property->hasDefaultValue(),
			'defaultValue'      => $hasDefault ? serialize($property->defaultValue()) : null,
			'isPromoted'        => $isPromoted = $property->isPromoted(),
			'promotedParameter' => $isPromoted ? self::parameterToExpectation($property->promotedParameter()) : null,
		];
	}

	private static function methodToExpectation(MethodReflection $method): array
	{
		return [
			'asString'       => (string) $method,
			'name'           => $method->name(),
			'location'       => $method->location(),
			'declaringType'  => self::typeToExpectation($method->declaringType()->type()),
			'attributes'     => self::attributesToExpectation($method->attributes()),
			'typeParameters' => array_map(self::typeParameterToExpectation(...), $method->typeParameters()),
			'parameters'     => array_map(self::parameterToExpectation(...), $method->parameters()),
			'returnType'     => self::typeToExpectation($method->returnType()),
		];
	}

	private static function parameterToExpectation(FunctionParameterReflection $parameter): array
	{
		return [
			'asString'        => (string) $parameter,
			'name'            => $parameter->name(),
			'location'        => $parameter->location(),
			'declaringMethod' => (string) $parameter->declaringMethod(),
			'type'            => self::typeToExpectation($parameter->type()),
			'hasDefaultValue' => $hasDefault = $parameter->hasDefaultValue(),
			'defaultValue'    => $hasDefault ? serialize($parameter->defaultValue()) : null,
		];
	}
}
