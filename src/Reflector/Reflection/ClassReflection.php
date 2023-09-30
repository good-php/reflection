<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
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
final class ClassReflection extends TypeReflection implements HasAttributes, HasTypeParameters
{
	private readonly NamedType $type;

	private NamedType $staticType;

	/** @var ReflectionClass<T> */
	private readonly ReflectionClass $nativeReflection;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	private readonly Attributes $attributes;

	private readonly ?NamedType $extends;

	/** @var Collection<int, NamedType> */
	private readonly Collection $implements;

	/** @var Collection<int, NamedType> */
	private readonly Collection $uses;

	/** @var Collection<int, PropertyReflection<$this>> */
	private readonly Collection $declaredProperties;

	/** @var Collection<int, PropertyReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>> */
	private readonly Collection $properties;

	/** @var Collection<int, MethodReflection<$this>> */
	private readonly Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>> */
	private readonly Collection $methods;

	/**
	 * @param ClassTypeDefinition<T> $definition
	 */
	public function __construct(
		private readonly ClassTypeDefinition $definition,
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
		unset($this->typeParameters, $this->extends, $that->implements, $that->uses, $that->declaredProperties, $that->properties, $that->declaredMethods, $that->methods);

		return $that;
	}

	public function type(): NamedType
	{
		return $this->type;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
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

	public function extends(): ?NamedType
	{
		if (isset($this->extends)) {
			return $this->extends;
		}

		if (!$this->definition->extends) {
			return null;
		}

		return $this->extends ??= TypeProjector::templateTypes(
			$this->definition->extends,
			$this->resolvedTypeParameterMap,
			$this->staticType,
		);
	}

	/**
	 * @return Collection<int, NamedType>
	 */
	public function implements(): Collection
	{
		return $this->implements ??= $this->definition
			->implements
			->map(fn (NamedType $type) => TypeProjector::templateTypes(
				$type,
				$this->resolvedTypeParameterMap,
				$this->staticType,
			));
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
				$this->staticType,
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
	 * @return Collection<int, PropertyReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>>
	 */
	public function properties(): Collection
	{
		if (isset($this->properties)) {
			return $this->properties;
		}

		/** @var Collection<int, NamedType> $types */
		$types = collect([
			$this->extends(),
			...$this->uses(),
		])->filter();

		$inheritedProperties = $types
			->flatMap(function (NamedType $type) {
				$reflection = $this->reflector->forNamedType($type);

				Assert::isInstanceOfAny($reflection, [self::class, TraitReflection::class]);
				/** @var self<object>|TraitReflection<object> $reflection */

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
	 * @return Collection<int, MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>>
	 */
	public function methods(): Collection
	{
		if (isset($this->methods)) {
			return $this->methods;
		}

		/** @var Collection<int, NamedType> $types */
		$types = collect([
			...$this->implements(),
			$this->extends(),
			...$this->uses(),
		])->filter();

		$inheritedMethods = $types
			->flatMap(function (NamedType $type) {
				$reflection = $this->reflector->forNamedType($type);

				Assert::isInstanceOfAny($reflection, [self::class, InterfaceReflection::class, TraitReflection::class]);
				/** @var self<object>|InterfaceReflection<object>|TraitReflection<object> $reflection */

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

	/**
	 * @return MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>|null
	 */
	public function constructor(): ?MethodReflection
	{
		return $this
			->methods()
			->first(fn (MethodReflection $reflection) => $reflection->name() === '__construct');
	}

	public function isAnonymous(): bool
	{
		return $this->definition->anonymous;
	}

	public function isAbstract(): bool
	{
		return $this->definition->abstract;
	}

	public function isFinal(): bool
	{
		return $this->definition->final;
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}

	/**
	 * @return T
	 */
	public function newInstance(mixed ...$args): object
	{
		return $this->nativeReflection()->newInstance(...$args);
	}

	/**
	 * @return T
	 */
	public function newInstanceWithoutConstructor(): object
	{
		return $this->nativeReflection()->newInstanceWithoutConstructor();
	}

	/**
	 * @return ReflectionClass<T>
	 */
	private function nativeReflection(): ReflectionClass
	{
		return $this->nativeReflection ??= new ReflectionClass($this->definition->qualifiedName);
	}
}
