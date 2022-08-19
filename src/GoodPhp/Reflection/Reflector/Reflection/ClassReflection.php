<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\PropertyDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
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
class ClassReflection extends TypeReflection implements HasAttributes
{
	/** @var Lazy<Type|null> */
	private Lazy $extends;

	/** @var Lazy<Collection<int, Type>> */
	private Lazy $implements;

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

	/** @var ReflectionClass<object> */
	private readonly ReflectionClass $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	public function __construct(
		private readonly ClassTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
		private readonly Reflector $reflector,
	) {
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
				->map(fn (Type $type) => TypeProjector::templateTypes(
					$type,
					$resolvedTypeParameterMap
				))
		);
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
			fn () => collect([
				$this->extends(),
				...$this->uses(),
			])
				->filter()
				->flatMap(function (Type $type) {
					$reflection = $this->reflector->forNamedType($type);

					return match (true) {
						$reflection instanceof self,
							$reflection instanceof TraitReflection => $reflection->properties(),
						default                                 => [],
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
			fn () => collect([
				...$this->implements(),
				$this->extends(),
				...$this->uses(),
			])
				->filter()
				->flatMap(function (Type $type) {
					$reflection = $this->reflector->forNamedType($type);

					return match (true) {
						$reflection instanceof self,
							$reflection instanceof InterfaceReflection,
							$reflection instanceof TraitReflection => $reflection->methods(),
						default                                 => [],
					};
				})
				->concat($this->declaredMethods->value())
				->keyBy(fn (MethodReflection $method) => $method->name())
				->values()
		);

		$this->nativeReflection = new ReflectionClass($this->definition->qualifiedName);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	public function extends(): ?Type
	{
		return $this->extends->value();
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function implements(): Collection
	{
		return $this->implements->value();
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
		return $this->declaredMethods
			->value()
			->reject(fn (MethodReflection $reflection) => $reflection->name() === '__construct');
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function methods(): Collection
	{
		return $this->methods
			->value()
			->reject(fn (MethodReflection $reflection) => $reflection->name() === '__construct');
	}

	/**
	 * @return MethodReflection<$this>|null
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

	public function newInstanceWithoutConstructor(): object
	{
		return $this->nativeReflection->newInstanceWithoutConstructor();
	}
}
