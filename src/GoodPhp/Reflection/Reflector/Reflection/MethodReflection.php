<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionMethod;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
class MethodReflection implements HasAttributes
{
	/** @var Lazy<Collection<int, FunctionParameterReflection<$this>>> */
	private Lazy $parameters;

	/** @var Lazy<Type|null> */
	private Lazy $returnType;

	private readonly ReflectionMethod $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly MethodDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->parameters = lazy(
			fn () => $this->definition
				->parameters
				->map(fn (FunctionParameterDefinition $parameter) => new FunctionParameterReflection($parameter, $this, $resolvedTypeParameterMap))
		);
		$this->returnType = lazy(
			fn () => $this->definition->returnType ?
				TypeProjector::templateTypes(
					$this->definition->returnType,
					$resolvedTypeParameterMap
				) :
				null
		);
		$this->nativeReflection = new ReflectionMethod($this->owner->qualifiedName(), $this->definition->name);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function name(): string
	{
		return $this->definition->name;
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

	/**
	 * @return Collection<int, FunctionParameterReflection<$this>>
	 */
	public function parameters(): Collection
	{
		return $this->parameters->value();
	}

	public function returnType(): ?Type
	{
		return $this->returnType->value();
	}

	public function invoke(object $receiver, mixed ...$args): mixed
	{
		return $this->nativeReflection->invoke($receiver, ...$args);
	}
}
