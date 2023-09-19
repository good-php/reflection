<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant T
 *
 * @extends TypeReflection<T>
 */
class TraitReflection extends TypeReflection implements HasAttributes
{
	/** @var Lazy<ReflectionClass<object>> */
	private readonly Lazy $nativeReflection;

	/** @var Lazy<Attributes> */
	private readonly Lazy $attributes;

	/** @var Lazy<Collection<int, Type>> */
	private Lazy $uses;

	/** @var Lazy<Collection<int, PropertyReflection<$this>>> */
	private Lazy $declaredProperties;

	/** @var Lazy<Collection<int, PropertyReflection<$this>>> */
	private Lazy $properties;

	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $declaredMethods;

	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $methods;

	public function __construct(
		private readonly TraitTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
		private readonly Reflector $reflector,
	) {
		$this->nativeReflection = lazy(fn () => new ReflectionClass($this->definition->qualifiedName));
		$this->attributes = lazy(fn () => new Attributes(
			fn () => $this->nativeReflection->value()->getAttributes()
		));
		$this->uses = lazy(
			fn () => $this->definition
				->uses
				->map(fn (Type $type) => TypeProjector::templateTypes(
					$type,
					$resolvedTypeParameterMap
				))
		);

		$this->declaredProperties = lazy(
			fn () => $this->definition
				->properties
				->map(fn (PropertyDefinition $property) => new PropertyReflection($property, $this, $resolvedTypeParameterMap))
		);
		$this->properties = lazy(
			fn () => $this->uses()
				->flatMap(function (Type $type) {
					$reflection = $this->reflector->forNamedType($type);

					return match (true) {
						$reflection instanceof ClassReflection,
						$reflection instanceof self => $reflection->properties(),
						default                     => [],
					};
				})
				->concat($this->declaredProperties->value())
				->keyBy(fn (PropertyReflection $property) => $property->name())
				->values()
		);

		$this->declaredMethods = lazy(
			fn () => $this->definition
				->methods
				->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, $resolvedTypeParameterMap))
		);
		$this->methods = lazy(
			fn () => $this->uses()
				->flatMap(function (Type $type) {
					$reflection = $this->reflector->forNamedType($type);

					return match (true) {
						$reflection instanceof ClassReflection,
						$reflection instanceof InterfaceReflection,
						$reflection instanceof self => $reflection->methods(),
						default                     => [],
					};
				})
				->concat($this->declaredMethods->value())
				->keyBy(fn (MethodReflection $method) => $method->name())
				->values()
		);
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
		return $this->attributes->value();
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function uses(): Collection
	{
		return $this->uses->value();
	}

	/**
	 * @return Collection<int, PropertyReflection<$this>>
	 */
	public function declaredProperties(): Collection
	{
		return $this->declaredProperties->value();
	}

	/**
	 * @return Collection<int, PropertyReflection<$this>>
	 */
	public function properties(): Collection
	{
		return $this->properties->value();
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods->value();
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function methods(): Collection
	{
		return $this->methods->value();
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}
}
