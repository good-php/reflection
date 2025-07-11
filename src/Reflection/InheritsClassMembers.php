<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\Traits\TraitAliasesMethodReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitAliasReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use Webmozart\Assert\Assert;

/**
 * @template ReflectableType of object
 */
trait InheritsClassMembers
{
	/**
	 * @param list<NamedType>|NamedType $types
	 *
	 * @return list<PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	protected function propertiesFromTypes(array|NamedType $types, NamedType $staticType, Reflector $reflector): array
	{
		return collect(is_array($types) ? $types : [$types])
			->flatMap(function (NamedType $type) use ($staticType, $reflector) {
				$reflection = $reflector->forNamedType($type);

				Assert::isInstanceOf($reflection, HasProperties::class);

				return $reflection
					->withStaticType($staticType)
					->properties();
			})
			->keyBy(fn (PropertyReflection $property) => $property->name())
			->values()
			->all();
	}

	/**
	 * @return list<PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	protected function propertiesFromTraits(UsedTraitsReflection $usedTraits, NamedType $staticType, Reflector $reflector): array
	{
		$types = array_map(
			fn (UsedTraitReflection $usedTrait) => $usedTrait->trait(),
			$usedTraits->traits()
		);

		return $this->propertiesFromTypes($types, $staticType, $reflector);
	}

	/**
	 * @param list<NamedType>|NamedType $types
	 *
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	protected function methodsFromTypes(array|NamedType $types, NamedType $staticType, Reflector $reflector): array
	{
		return collect(is_array($types) ? $types : [$types])
			->flatMap(function (NamedType $type) use ($staticType, $reflector) {
				$reflection = $reflector->forNamedType($type);

				Assert::isInstanceOf($reflection, HasMethods::class);

				return $reflection
					->withStaticType($staticType)
					->methods();
			})
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values()
			->all();
	}

	/**
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	protected function methodsFromTraits(UsedTraitsReflection $usedTraits, NamedType $staticType, Reflector $reflector): array
	{
		return collect($usedTraits->traits())
			->flatMap(function (UsedTraitReflection $usedTrait) use ($staticType, $reflector, $usedTraits) {
				$traitExcludedMethods = $usedTraits->excludedTraitMethods()[$usedTrait->trait()->name] ?? [];

				$reflection = $reflector->forNamedType($usedTrait->trait());

				Assert::isInstanceOf($reflection, TraitReflection::class);
				/** @var TraitReflection<object> $reflection */

				return collect($reflection->withStaticType($staticType)->methods())
					->reject(fn (MethodReflection $method) => in_array($method->name(), $traitExcludedMethods, true))
					->flatMap(function (MethodReflection $method) use ($usedTrait) {
						/** @var MethodReflection<ReflectableType, HasMethods<ReflectableType>> $method */
						return $this->aliasMethod($method, $usedTrait->aliases());
					});
			})
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values()
			->all();
	}

	/**
	 * @param MethodReflection<ReflectableType, HasMethods<ReflectableType>> $method
	 * @param list<UsedTraitAliasReflection>                                 $aliases
	 *
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	private function aliasMethod(MethodReflection $method, array $aliases): array
	{
		$result = [$method->name() => $method];
		$aliasesForMethod = array_filter($aliases, fn (UsedTraitAliasReflection $alias) => $alias->name() === $method->name());

		foreach ($aliasesForMethod as $alias) {
			$newName = $alias->newName() ?? $alias->name();

			$result[$newName] = new TraitAliasesMethodReflection($method, $alias);
		}

		return array_values($result);
	}
}
