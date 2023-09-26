<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TraitTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;
use TenantCloud\Standard\Lazy\Lazy;
use Webmozart\Assert\Assert;

use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant T of object
 *
 * @extends TypeReflection<T>
 */
final class TraitReflection extends TypeReflection implements HasAttributes, HasTypeParameters
{
	/** @var Lazy<ReflectionClass<T>> */
	private readonly Lazy $nativeReflection;

	/** @var Lazy<Attributes> */
	private readonly Lazy $attributes;

	/** @var Lazy<Collection<int, NamedType>> */
	private Lazy $uses;

	/** @var Lazy<Collection<int, PropertyReflection<$this>>> */
	private Lazy $declaredProperties;

	/** @var Lazy<Collection<int, PropertyReflection<$this|self<object>>>> */
	private Lazy $properties;

	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $declaredMethods;

	/** @var Lazy<Collection<int, MethodReflection<$this|self<object>>>> */
	private Lazy $methods;

	/**
	 * @param TraitTypeDefinition<T> $definition
	 */
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
				->map(fn (NamedType $type) => TypeProjector::templateTypes(
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
			function () {
				$methods = $this->uses()
					->flatMap(function (NamedType $type) {
						$reflection = $this->reflector->forNamedType($type);

						Assert::isInstanceOf($reflection, self::class);
						/** @var self<object> $reflection */

						return $reflection->properties();
					});

				return collect([...$methods, ...$this->declaredProperties->value()])
					->keyBy(fn (PropertyReflection $property) => $property->name())
					->values();
			}
		);

		$this->declaredMethods = lazy(
			fn () => $this->definition
				->methods
				->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, $resolvedTypeParameterMap))
		);
		$this->methods = lazy(
			fn () => $this->uses()
				->flatMap(function (NamedType $type) {
					$reflection = $this->reflector->forNamedType($type);

					Assert::isInstanceOf($reflection, self::class);
					/** @var self<object> $reflection */

					return $reflection->methods();
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
	 * @return Collection<int, NamedType>
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
	 * @return Collection<int, PropertyReflection<$this|self<object>>>
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
	 * @return Collection<int, MethodReflection<$this|self<object>>>
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
