<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Traits\NpdUsedTraitsReflection;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\InheritsClassMembers;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\Properties\HasProperties;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\Traits\UsedTraitsReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use ReflectionClass;

/**
 * @template ReflectableType of object
 *
 * @implements TraitReflection<ReflectableType>
 */
final class NpdTraitReflection extends NpdTypeReflection implements TraitReflection
{
	/** @use InheritsClassMembers<ReflectableType> */
	use InheritsClassMembers;

	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionClass<ReflectableType> */
	private readonly ReflectionClass $nativeReflection;

	/** @var list<TypeParameterReflection<$this>> */
	private array $typeParameters;

	private readonly Attributes $attributes;

	private UsedTraitsReflection $uses;

	/** @var list<PropertyReflection<ReflectableType, $this>> */
	private array $declaredProperties;

	/** @var list<PropertyReflection<ReflectableType, HasProperties<ReflectableType>>> */
	private array $properties;

	/** @var list<MethodReflection<ReflectableType, $this>> */
	private array $declaredMethods;

	/** @var list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>> */
	private array $methods;

	/**
	 * @param TraitTypeDefinition<ReflectableType> $definition
	 */
	public function __construct(
		private readonly TraitTypeDefinition $definition,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
		private readonly Reflector $reflector,
	) {
		$this->type = new NamedType($this->qualifiedName(), $this->resolvedTypeParameterMap->toArguments($this->definition->typeParameters));
		$this->staticType = $this->type;
	}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($this->typeParameters, $that->uses, $that->declaredProperties, $that->properties, $that->declaredMethods, $that->methods);

		return $that;
	}

	public function type(): NamedType
	{
		return $this->type;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	public function attributes(): Attributes
	{
		return $this->attributes ??= new NativeAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return list<TypeParameterReflection<$this>>
	 */
	public function typeParameters(): array
	{
		return $this->typeParameters ??= array_map(
			fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->staticType),
			$this->definition->typeParameters
		);
	}

	public function uses(): UsedTraitsReflection
	{
		return $this->uses ??= new NpdUsedTraitsReflection($this->definition->uses, $this->resolvedTypeParameterMap, $this->staticType);
	}

	/**
	 * @return list<PropertyReflection<ReflectableType, $this>>
	 */
	public function declaredProperties(): array
	{
		return $this->declaredProperties ??= array_map(
			fn (PropertyDefinition $property) => new NpdPropertyReflection($property, $this, $this->staticType, $this->resolvedTypeParameterMap),
			$this->definition->properties,
		);
	}

	/**
	 * @return list<PropertyReflection<ReflectableType, HasProperties<ReflectableType>>>
	 */
	public function properties(): array
	{
		return $this->properties ??= collect([
			...$this->propertiesFromTraits($this->uses(), $this->staticType, $this->reflector),
			...$this->declaredProperties(),
		])
			->keyBy(fn (PropertyReflection $property) => $property->name())
			->values()
			->all();
	}

	/**
	 * @return list<MethodReflection<ReflectableType, $this>>
	 */
	public function declaredMethods(): array
	{
		return $this->declaredMethods ??= array_map(
			fn (MethodDefinition $method) => new NpdMethodReflection($method, $this, $this->staticType, $this->resolvedTypeParameterMap),
			$this->definition->methods
		);
	}

	/**
	 * @return list<MethodReflection<ReflectableType, HasMethods<ReflectableType>>>
	 */
	public function methods(): array
	{
		return $this->methods ??= collect([
			...$this->methodsFromTraits($this->uses(), $this->staticType, $this->reflector),
			...$this->declaredMethods(),
		])
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values()
			->all();
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}

	/**
	 * @return ReflectionClass<ReflectableType>
	 */
	private function nativeReflection(): ReflectionClass
	{
		return $this->nativeReflection ??= new ReflectionClass($this->definition->qualifiedName);
	}
}
