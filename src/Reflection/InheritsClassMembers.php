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
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

/**
 * @template ReflectableType of object
 */
trait InheritsClassMembers
{
	/**
	 * @param Collection<int, NamedType>|NamedType $types
	 *
	 * @return Collection<int, PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	protected function propertiesFromTypes(Collection|NamedType $types, NamedType $staticType, Reflector $reflector): Collection
	{
		/** @var Collection<int, PropertyReflection<ReflectableType, HasProperties<ReflectableType>>> */
		return Collection::wrap($types)
			->flatMap(function (NamedType $type) use ($staticType, $reflector) {
				$reflection = $reflector->forNamedType($type);

				Assert::isInstanceOf($reflection, HasProperties::class);

				return $reflection
					->withStaticType($staticType)
					->properties();
			})
			->keyBy(fn (PropertyReflection $property) => $property->name())
			->values();
	}

	/**
	 * @return Collection<int, PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	protected function propertiesFromTraits(UsedTraitsReflection $usedTraits, NamedType $staticType, Reflector $reflector): Collection
	{
		$types = $usedTraits
			->traits()
			->map(fn (UsedTraitReflection $usedTrait) => $usedTrait->trait());

		return $this->propertiesFromTypes($types, $staticType, $reflector);
	}

	/**
	 * @param Collection<int, NamedType>|NamedType $types
	 *
	 * @return Collection<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	protected function methodsFromTypes(Collection|NamedType $types, NamedType $staticType, Reflector $reflector): Collection
	{
		/** @var Collection<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>> */
		return Collection::wrap($types)
			->flatMap(function (NamedType $type) use ($staticType, $reflector) {
				$reflection = $reflector->forNamedType($type);

				Assert::isInstanceOf($reflection, HasMethods::class);

				return $reflection
					->withStaticType($staticType)
					->methods();
			})
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values();
	}

	/**
	 * @return Collection<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	protected function methodsFromTraits(UsedTraitsReflection $usedTraits, NamedType $staticType, Reflector $reflector): Collection
	{
		return $usedTraits
			->traits()
			->flatMap(function (UsedTraitReflection $usedTrait) use ($staticType, $reflector, $usedTraits) {
				$traitExcludedMethods = $usedTraits->excludedTraitMethods()[$usedTrait->trait()->name] ?? collect();

				$reflection = $reflector->forNamedType($usedTrait->trait());

				Assert::isInstanceOf($reflection, TraitReflection::class);
				/** @var TraitReflection<object> $reflection */

				return $reflection
					->withStaticType($staticType)
					->methods()
					->reject(fn (MethodReflection $method) => $traitExcludedMethods->contains($method->name()))
					->flatMap(function (MethodReflection $method) use ($usedTrait) {
						/** @var MethodReflection<ReflectableType, HasMethods<ReflectableType>> $method */
						return $this->aliasMethod($method, $usedTrait->aliases());
					});
			})
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values();
	}

	/**
	 * @param MethodReflection<ReflectableType, HasMethods<ReflectableType>> $method
	 * @param Collection<int, UsedTraitAliasReflection>                      $aliases
	 *
	 * @return array<int, MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	private function aliasMethod(MethodReflection $method, Collection $aliases): array
	{
		$result = [$method->name() => $method];
		$aliasesForMethod = $aliases->filter(fn (UsedTraitAliasReflection $alias) => $alias->name() === $method->name());

		foreach ($aliasesForMethod as $alias) {
			$newName = $alias->newName() ?? $alias->name();

			$result[$newName] = new TraitAliasesMethodReflection($method, $alias);
		}

		return array_values($result);
	}
}
