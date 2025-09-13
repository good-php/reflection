<?php

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Methods\MergedInheritanceMethodReflection;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\Properties\MergedInheritancePropertyReflection;
use GoodPhp\Reflection\Reflection\Traits\TraitAliasesMethodReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitAliasReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

class ClassMemberInheritanceResolver
{
	/**
	 * @template ReflectableType of object
	 *
	 * @param list<PropertyReflection<ReflectableType, HasProperties<ReflectableType>>> $declaredProperties
	 * @param list<NamedType>                                                           $implements
	 *
	 * @return list<PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	public function properties(
		Reflector $reflector,
		NamedType $staticType,
		array $declaredProperties,
		?NamedType $extends = null,
		array $implements = [],
		?UsedTraitsReflection $usedTraits = null,
	): array {
		return collect([
			...$declaredProperties,
			...($extends ? $this->propertiesFromTypes($extends, $staticType, $reflector) : []),
			...($usedTraits ? $this->propertiesFromTraits($usedTraits, $staticType, $reflector) : []),
			...$this->propertiesFromTypes($implements, $staticType, $reflector),
		])
			->groupBy(fn (PropertyReflection $property) => $property->name())
			->map(fn (Collection $sameProperties) => MergedInheritancePropertyReflection::merge($sameProperties->all()))
			->values()
			->all();
	}

	/**
	 * @template ReflectableType of object
	 *
	 * @param list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>> $declaredMethods
	 * @param list<NamedType>                                                      $implements
	 *
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	public function methods(
		Reflector $reflector,
		NamedType $staticType,
		array $declaredMethods,
		?NamedType $extends = null,
		array $implements = [],
		?UsedTraitsReflection $usedTraits = null,
	): array {
		return collect([
			...$declaredMethods,
			...($extends ? $this->methodsFromTypes($extends, $staticType, $reflector) : []),
			...($usedTraits ? $this->methodsFromTraits($usedTraits, $staticType, $reflector) : []),
			...$this->methodsFromTypes($implements, $staticType, $reflector),
		])
			->groupBy(fn (MethodReflection $method) => $method->name())
			->map(fn (Collection $sameMethods) => MergedInheritanceMethodReflection::merge($sameMethods->all()))
			->values()
			->all();
	}

	/**
	 * @param list<NamedType>|NamedType $types
	 *
	 * @return list<PropertyReflection<*, HasProperties<*>>>
	 */
	protected function propertiesFromTypes(array|NamedType $types, NamedType $staticType, Reflector $reflector): array
	{
		return collect(is_array($types) ? $types : [$types])
			->flatMap(function (NamedType $type) use ($staticType, $reflector) {
				$reflection = $reflector->forNamedType($type);

				if (!$reflection instanceof HasProperties) {
					return [];
				}

				return $reflection
					->withStaticType($staticType)
					->properties();
			})
			->all();
	}

	/**
	 * @return list<PropertyReflection<*, HasProperties<*>>>
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
	 * @return list<MethodReflection<*, HasMethods<*>>>
	 */
	protected function methodsFromTypes(array|NamedType $types, NamedType $staticType, Reflector $reflector): array
	{
		return collect(is_array($types) ? $types : [$types])
			->flatMap(function (NamedType $type) use ($staticType, $reflector) {
				$reflection = $reflector->forNamedType($type);

				if (!$reflection instanceof HasMethods) {
					return [];
				}

				return $reflection
					->withStaticType($staticType)
					->methods();
			})
			->all();
	}

	/**
	 * @return list<MethodReflection<*, HasMethods<*>>>
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
					->flatMap(fn (MethodReflection $method) => $this->aliasMethod($method, $usedTrait->aliases()));
			})
			->all();
	}

	/**
	 * @template ReflectableType of object
	 *
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
