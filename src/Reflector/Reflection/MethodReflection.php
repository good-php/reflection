<?php declare(strict_types=1);

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflector\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Stringable;

/**
 * @template-covariant DeclaringTypeReflection of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
final class MethodReflection implements Stringable, HasAttributes, HasTypeParameters
{
	private readonly ReflectionMethod $nativeReflection;

	/** @var Collection<int, TypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	private readonly Attributes $attributes;

	/** @var Collection<int, FunctionParameterReflection<$this>> */
	private Collection $parameters;

	private ?Type $returnType;

	/**
	 * @param DeclaringTypeReflection $declaringType
	 */
	public function __construct(
		private readonly MethodDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $declaringType,
		public NamedType $staticType,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($this->typeParameters, $this->parameters, $that->returnType);

		return $that;
	}

	public function name(): string
	{
		return $this->definition->name;
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
	 * @return Collection<int, FunctionParameterReflection<$this>>
	 */
	public function parameters(): Collection
	{
		return $this->parameters ??= $this->definition
			->parameters
			->map(fn (FunctionParameterDefinition $parameter) => new FunctionParameterReflection($parameter, $this, $this->staticType, $this->resolvedTypeParameterMap));
	}

	public function returnType(): ?Type
	{
		if (isset($this->returnType)) {
			return $this->returnType;
		}

		if (!$this->definition->returnType) {
			return null;
		}

		return $this->returnType ??= TypeProjector::templateTypes(
			$this->definition->returnType,
			$this->resolvedTypeParameterMap,
			$this->staticType,
		);
	}

	/**
	 * Call a method with strict_types=0.
	 */
	public function invoke(object $receiver, mixed ...$args): mixed
	{
		return $this->nativeReflection()->invoke($receiver, ...$args);
	}

	/**
	 * Call a public method with strict_types=1.
	 */
	public function invokeStrict(object $receiver, mixed ...$args): mixed
	{
		// TODO: generic type for $receiver
		/* @phpstan-ignore-next-line method.notFound */
		return (fn () => $this->{$this->name()})->call($receiver, ...$args);
	}

	public function location(): string
	{
		return $this->declaringType->location() . '::' . $this;
	}

	private function nativeReflection(): ReflectionMethod
	{
		return $this->nativeReflection ??= new ReflectionMethod($this->declaringType->qualifiedName(), $this->definition->name);
	}

	public function __toString(): string
	{
		return $this->name() . '()';
	}
}
