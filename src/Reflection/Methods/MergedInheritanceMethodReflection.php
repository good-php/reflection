<?php

namespace GoodPhp\Reflection\Reflection\Methods;

use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\Functions\MergedInheritanceFunctionParameterReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\MethodReflectionDefaults;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParametersDefaults;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 * Generally, method reflection wouldn't need to be merged, as technically the "topmost" method
 * takes priority, and supertypes' definitions of the same method are ignored completely.
 *
 * However, PHPDoc types are inherited from supertypes. This, unfortunately, means
 * we have to keep track of all methods and merge their types as good we can.
 *
 * @template-contravariant ReflectableType of object
 *
 * @implements MethodReflection<ReflectableType>
 */
final class MergedInheritanceMethodReflection implements MethodReflection
{
	use HasTypeParametersDefaults;

	use MethodReflectionDefaults;

	/** @var list<TypeParameterReflection> */
	private array $typeParameters;

	/** @var list<FunctionParameterReflection> */
	private array $parameters;

	/** @var MethodReflection<ReflectableType> */
	private MethodReflection $returnTypeFromReflection;

	/**
	 * @param list<MethodReflection<ReflectableType>> $reflections
	 */
	private function __construct(
		private readonly array $reflections,
	) {}

	/**
	 * @template ReflectableTypeScoped of object
	 *
	 * @param list<MethodReflection<ReflectableTypeScoped>> $reflections
	 *
	 * @return MethodReflection<ReflectableTypeScoped>
	 */
	public static function merge(array $reflections): MethodReflection
	{
		Assert::notEmpty($reflections);

		if (count($reflections) === 1) {
			return $reflections[0];
		}

		return new self($reflections);
	}

	public function attributes(): Attributes
	{
		return $this->reflections[0]->attributes();
	}

	public function typeParameters(): array
	{
		if (isset($this->typeParameters)) {
			return $this->typeParameters;
		}

		// First PHPDoc (top to bottom) with any @template tag - wins. The rest are ignored.
		$firstMethodWithTypeParameters = Arr::first($this->reflections, fn (MethodReflection $reflection) => (bool) $reflection->typeParameters());

		return $this->typeParameters = $firstMethodWithTypeParameters?->typeParameters() ?? [];
	}

	public function withStaticType(NamedType $staticType): static
	{
		return new self(
			array_map(fn (MethodReflection $reflection) => $reflection->withStaticType($staticType), $this->reflections),
		);
	}

	public function name(): string
	{
		return $this->reflections[0]->name();
	}

	public function parameters(): array
	{
		if (isset($this->parameters)) {
			return $this->parameters;
		}

		return $this->parameters ??= array_map(fn (int $index) => MergedInheritanceFunctionParameterReflection::merge(
			array_values(
				array_filter(
					array_map(fn (MethodReflection $method) => $method->parameter($index), $this->reflections)
				),
			),
			$this,
		), array_keys($this->reflections[0]->parameters()));
	}

	public function returnType(): ?Type
	{
		return $this->returnTypeFromReflection()->returnType();
	}

	public function returnTypeSource(): ?TypeSource
	{
		return $this->returnTypeFromReflection()->returnTypeSource();
	}

	public function invoke(object $receiver, ...$args): mixed
	{
		return $this->reflections[0]->invoke($receiver, ...$args);
	}

	public function invokeLax(object $receiver, ...$args): mixed
	{
		return $this->reflections[0]->invokeLax($receiver, ...$args);
	}

	public function location(): string
	{
		return $this->reflections[0]->location();
	}

	/**
	 * @return HasMethods<ReflectableType>
	 */
	public function declaringType(): HasMethods
	{
		return $this->reflections[0]->declaringType();
	}

	/**
	 * @return MethodReflection<ReflectableType>
	 */
	private function returnTypeFromReflection(): MethodReflection
	{
		if (isset($this->returnTypeFromReflection)) {
			return $this->returnTypeFromReflection;
		}

		// First @return in the inheritance tree - overwrites the native typehint
		$firstMethodWithPhpDocReturn = Arr::first($this->reflections, fn (MethodReflection $reflection) => $reflection->returnTypeSource() === TypeSource::PHP_DOC);

		return $this->returnTypeFromReflection = $firstMethodWithPhpDocReturn ?? $this->reflections[0];
	}

	public function __toString(): string
	{
		return (string) $this->reflections[0];
	}
}
