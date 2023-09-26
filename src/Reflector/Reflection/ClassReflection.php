<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
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
final class ClassReflection extends TypeReflection implements HasAttributes, HasTypeParameters
{
	/** @var Lazy<ReflectionClass<T>> */
	private readonly Lazy $nativeReflection;

	/** @var Lazy<Attributes> */
	private readonly Lazy $attributes;

	/** @var Lazy<NamedType|null> */
	private Lazy $extends;

	/** @var Lazy<Collection<int, NamedType>> */
	private Lazy $implements;

	/** @var Lazy<Collection<int, NamedType>> */
	private Lazy $uses;

	/** @var Lazy<Collection<int, PropertyReflection<$this>>> */
	private Lazy $declaredProperties;

	/** @var Lazy<Collection<int, PropertyReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>>> */
	private Lazy $properties;

	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $declaredMethods;

	/** @var Lazy<Collection<int, MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>>> */
	private Lazy $methods;

	/**
	 * @param ClassTypeDefinition<T> $definition
	 */
	public function __construct(
		private readonly ClassTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
		private readonly Reflector $reflector,
	) {
		$this->nativeReflection = lazy(fn () => new ReflectionClass($this->definition->qualifiedName));
		$this->attributes = lazy(fn () => new Attributes(
			fn () => $this->nativeReflection->value()->getAttributes()
		));
		$this->extends = lazy(
			fn () => $this->definition->extends ?
				TypeProjector::templateTypes(
					$this->definition->extends,
					$resolvedTypeParameterMap
				) :
				null
		);
		$this->implements = lazy(
			fn () => $this->definition
				->implements
				->map(fn (NamedType $type) => TypeProjector::templateTypes(
					$type,
					$resolvedTypeParameterMap
				))
		);
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
				/** @var Collection<int, NamedType> $types */
				$types = collect([
					$this->extends(),
					...$this->uses(),
				])->filter();

				return $types
					->flatMap(function (NamedType $type) {
						$reflection = $this->reflector->forNamedType($type);

						Assert::isInstanceOfAny($reflection, [self::class, TraitReflection::class]);
						/** @var self<object>|TraitReflection<object> $reflection */

						return $reflection->properties();
					})
					->concat($this->declaredProperties->value())
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
			function () {
				/** @var Collection<int, NamedType> $types */
				$types = collect([
					...$this->implements(),
					$this->extends(),
					...$this->uses(),
				])->filter();

				return $types
					->flatMap(function (NamedType $type) {
						$reflection = $this->reflector->forNamedType($type);

						Assert::isInstanceOfAny($reflection, [self::class, InterfaceReflection::class, TraitReflection::class]);
						/** @var self<object>|InterfaceReflection<object>|TraitReflection<object> $reflection */

						return $reflection->methods();
					})
					->concat($this->declaredMethods->value())
					->keyBy(fn (MethodReflection $method) => $method->name())
					->values();
			}
		);
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
		return $this->attributes->value();
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	public function extends(): ?NamedType
	{
		return $this->extends->value();
	}

	/**
	 * @return Collection<int, NamedType>
	 */
	public function implements(): Collection
	{
		return $this->implements->value();
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
	 * @return Collection<int, PropertyReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>>
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
		return $this->declaredMethods
			->value()
			->reject(fn (MethodReflection $reflection) => $reflection->name() === '__construct')
			->values();
	}

	/**
	 * @return Collection<int, MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>>
	 */
	public function methods(): Collection
	{
		return $this->methods
			->value()
			->reject(fn (MethodReflection $reflection) => $reflection->name() === '__construct')
			->values();
	}

	/**
	 * @return MethodReflection<$this|self<object>|InterfaceReflection<object>|TraitReflection<object>>|null
	 */
	public function constructor(): ?MethodReflection
	{
		return $this->methods
			->value()
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

	public function newInstance(mixed ...$args): object
	{
		return $this->nativeReflection->value()->newInstance(...$args);
	}

	public function newInstanceWithoutConstructor(): object
	{
		return $this->nativeReflection->value()->newInstanceWithoutConstructor();
	}
}
