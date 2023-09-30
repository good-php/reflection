<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;
use Webmozart\Assert\Assert;

/**
 * @template-covariant T of object
 *
 * @extends TypeReflection<T>
 */
final class TraitReflection extends TypeReflection implements HasAttributes, HasTypeParameters
{
	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionClass<T> */
	private readonly ReflectionClass $nativeReflection;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	private readonly Attributes $attributes;

	/** @var Collection<int, NamedType> */
	private Collection $uses;

	/** @var Collection<int, PropertyReflection<$this>> */
	private Collection $declaredProperties;

	/** @var Collection<int, PropertyReflection<$this|self<object>>> */
	private Collection $properties;

	/** @var Collection<int, MethodReflection<$this>> */
	private Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<$this|self<object>>> */
	private Collection $methods;

	/**
	 * @param TraitTypeDefinition<T> $definition
	 */
	public function __construct(
		private readonly TraitTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
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
		return $this->attributes ??= new Attributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return Collection<int, TypeParameterReflection<$this>>
	 */
	public function typeParameters(): Collection
	{
		return $this->typeParameters ??= $this->definition
			->typeParameters
			->map(fn (TypeParameterDefinition $parameter) => new TypeParameterReflection($parameter, $this, $this->staticType));
	}

	/**
	 * @return Collection<int, NamedType>
	 */
	public function uses(): Collection
	{
		return $this->uses ??= $this->definition
			->uses
			->map(fn (NamedType $type) => TypeProjector::templateTypes(
				$type,
				$this->resolvedTypeParameterMap,
				$this->staticType
			));
	}

	/**
	 * @return Collection<int, PropertyReflection<$this>>
	 */
	public function declaredProperties(): Collection
	{
		return $this->declaredProperties ??= $this->definition
			->properties
			->map(fn (PropertyDefinition $property) => new PropertyReflection($property, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, PropertyReflection<$this|self<object>>>
	 */
	public function properties(): Collection
	{
		if (isset($this->properties)) {
			return $this->properties;
		}

		$inheritedProperties = $this->uses()
			->flatMap(function (NamedType $type) {
				$reflection = $this->reflector->forNamedType($type);

				Assert::isInstanceOf($reflection, self::class);
				/** @var self<object> $reflection */

				return $reflection
					->withStaticType($this->staticType)
					->properties();
			});

		/* @phpstan-ignore-next-line return.type, assign.propertyType */
		return $this->properties ??= collect([...$inheritedProperties, ...$this->declaredProperties()])
			->keyBy(fn (PropertyReflection $property) => $property->name())
			->values()
			->map(fn (PropertyReflection $property) => $property->withStaticType($this->staticType));
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods ??= $this->definition
			->methods
			->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	/**
	 * @return Collection<int, MethodReflection<$this|self<object>>>
	 */
	public function methods(): Collection
	{
		if (isset($this->methods)) {
			return $this->methods;
		}

		$inheritedMethods = $this->uses()
			->flatMap(function (NamedType $type) {
				$reflection = $this->reflector->forNamedType($type);

				Assert::isInstanceOf($reflection, self::class);
				/** @var self<object> $reflection */

				return $reflection
					->withStaticType($this->staticType)
					->methods();
			});

		/* @phpstan-ignore-next-line return.type, assign.propertyType */
		return $this->methods ??= collect([...$inheritedMethods, ...$this->declaredMethods()])
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values()
			->map(fn (MethodReflection $method) => $method->withStaticType($this->staticType));
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}

	/**
	 * @return ReflectionClass<T>
	 */
	private function nativeReflection(): ReflectionClass
	{
		return $this->nativeReflection ??= new ReflectionClass($this->definition->qualifiedName);
	}
}
