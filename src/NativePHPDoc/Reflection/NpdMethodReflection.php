<?php declare(strict_types=1);

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NpdAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionMethod;

/**
 * @template-covariant DeclaringTypeReflection of NpdClassReflection|NpdInterfaceReflection|NpdTraitReflection|NpdEnumReflection
 *
 * @implements MethodReflection<DeclaringTypeReflection>
 */
final class NpdMethodReflection implements MethodReflection
{
	private readonly ReflectionMethod $nativeReflection;

	/** @var Collection<int, NpdTypeParameterReflection<$this>> */
	private readonly Collection $typeParameters;

	private readonly NpdAttributes $attributes;

	/** @var Collection<int, NpdFunctionParameterReflection<$this>> */
	private Collection $parameters;

	private ?Type $returnType;

	/**
	 * @param NpdClassReflection $declaringType
	 */
	public function __construct(
		private readonly MethodDefinition $definition,
		private readonly NpdClassReflection|NpdInterfaceReflection|NpdTraitReflection|NpdEnumReflection $declaringType,
		private NamedType $staticType,
		private readonly TypeParameterMap $resolvedTypeParameterMap,
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

	public function attributes(): NpdAttributes
	{
		return $this->attributes ??= new NpdAttributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return Collection<int, NpdTypeParameterReflection<$this>>
	 */
	public function typeParameters(): Collection
	{
		return $this->typeParameters ??= $this->definition
			->typeParameters
			->map(fn (TypeParameterDefinition $parameter) => new NpdTypeParameterReflection($parameter, $this, $this->staticType));
	}

	/**
	 * @return Collection<int, NpdFunctionParameterReflection<$this>>
	 */
	public function parameters(): Collection
	{
		return $this->parameters ??= $this->definition
			->parameters
			->map(fn (FunctionParameterDefinition $parameter) => new NpdFunctionParameterReflection($parameter, $this, $this->staticType, $this->resolvedTypeParameterMap));
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

	public function invoke(object $receiver, mixed ...$args): mixed
	{
		// TODO: generic type for $receiver
		/* @phpstan-ignore-next-line method.notFound */
		return (fn () => $this->{$this->name()})->call($receiver, ...$args);
	}

	public function invokeLax(object $receiver, mixed ...$args): mixed
	{
		return $this->nativeReflection()->invoke($receiver, ...$args);
	}

	public function location(): string
	{
		return $this->declaringType->location() . '::' . $this;
	}

	/**
	 * @return DeclaringTypeReflection
	 */
	public function declaringType(): NpdClassReflection|NpdInterfaceReflection|NpdTraitReflection|NpdEnumReflection
	{
		return $this->declaringType;
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
