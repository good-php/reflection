<?php

namespace GoodPhp\Reflection\Reflection\Functions;

use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\MethodReflection;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class MergedInheritanceFunctionParameterReflection implements FunctionParameterReflection
{
	private readonly FunctionParameterReflection $typeFromReflection;

	/**
	 * @param list<FunctionParameterReflection> $reflections
	 * @param MethodReflection<*> $declaringMethod
	 */
	private function __construct(
		private readonly array $reflections,
		private readonly MethodReflection $declaringMethod,
	) {}

	/**
	 * @param list<FunctionParameterReflection> $reflections
	 * @param MethodReflection<*> $declaringMethod
	 */
	public static function merge(array $reflections, MethodReflection $declaringMethod): FunctionParameterReflection
	{
		Assert::notEmpty($reflections);

		if (count($reflections) === 1) {
			return $reflections[0];
		}

		return new self($reflections, $declaringMethod);
	}

	public function attributes(): Attributes
	{
		return $this->reflections[0]->attributes();
	}

	public function name(): string
	{
		return $this->reflections[0]->name();
	}

	public function type(): ?Type
	{
		return $this->typeFromReflection()->type();
	}

	public function typeSource(): ?TypeSource
	{
		return $this->typeFromReflection()->typeSource();
	}

	public function hasDefaultValue(): bool
	{
		return $this->reflections[0]->hasDefaultValue();
	}

	public function defaultValue(): mixed
	{
		return $this->reflections[0]->defaultValue();
	}

	public function location(): string
	{
		return $this->reflections[0]->location();
	}

	public function declaringMethod(): MethodReflection
	{
		return $this->declaringMethod;
	}

	private function typeFromReflection(): FunctionParameterReflection
	{
		if (isset($this->typeFromReflection)) {
			return $this->typeFromReflection;
		}

		// First @param in the inheritance tree - overwrites the native typehint
		$firstMethodWithPhpDocParam = Arr::first($this->reflections, fn (FunctionParameterReflection $reflection) => $reflection->typeSource() === TypeSource::PHP_DOC);

		return $this->typeFromReflection = $firstMethodWithPhpDocParam ?? $this->reflections[0];
	}

	public function __toString(): string
	{
		return (string) $this->reflections[0];
	}
}
