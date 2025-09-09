<?php declare(strict_types=1);

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes\NativeAttributes;
use GoodPhp\Reflection\NativePHPDoc\Reflection\TypeParameters\NpdTypeParameterReflection;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\MethodReflectionDefaults;
use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParametersDefaults;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use ReflectionMethod;

/**
 * @template-contravariant ReflectableType of object
 *
 * @template-covariant DeclaringTypeReflection of HasMethods<ReflectableType>
 *
 * @implements MethodReflection<ReflectableType, DeclaringTypeReflection>
 */
final class NpdMethodReflection implements MethodReflection
{
	/** @use HasTypeParametersDefaults<$this> */
	use HasTypeParametersDefaults;

	/** @use MethodReflectionDefaults<ReflectableType, DeclaringTypeReflection> */
	use MethodReflectionDefaults;

	private readonly ReflectionMethod $nativeReflection;

	/** @var list<TypeParameterReflection<$this>> */
	private array $typeParameters;

	private readonly Attributes $attributes;

	/** @var list<FunctionParameterReflection<$this>> */
	private array $parameters;

	private ?Type $returnType;

	/**
	 * @param DeclaringTypeReflection $declaringType
	 */
	public function __construct(
		private readonly MethodDefinition $definition,
		private readonly HasMethods $declaringType,
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

	/**
	 * @return list<FunctionParameterReflection<$this>>
	 */
	public function parameters(): array
	{
		return $this->parameters ??= array_map(
			fn (FunctionParameterDefinition $parameter) => new NpdFunctionParameterReflection($parameter, $this, $this->staticType, $this->resolvedTypeParameterMap),
			$this->definition->parameters
		);
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
		$methodName = $this->name();

		return (fn () => $this->{$methodName}(...$args))->call($receiver);
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
	public function declaringType(): HasMethods
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
