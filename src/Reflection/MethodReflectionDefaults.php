<?php declare(strict_types=1);

namespace GoodPhp\Reflection\Reflection;

use GoodPhp\Reflection\Reflection\Methods\HasMethods;
use Illuminate\Support\Arr;

/**
 * @template-contravariant ReflectableType of object
 *
 * @template-covariant DeclaringTypeReflection of HasMethods<ReflectableType>
 */
trait MethodReflectionDefaults
{
	/**
	 * @return list<FunctionParameterReflection<self<ReflectableType, DeclaringTypeReflection>>>
	 */
	abstract public function parameters(): array;

	/**
	 * @return FunctionParameterReflection<self<ReflectableType, DeclaringTypeReflection>>|null
	 */
	public function parameter(string|int $nameOrIndex): ?FunctionParameterReflection
	{
		if (is_int($nameOrIndex)) {
			return $this->parameters()[$nameOrIndex] ?? null;
		}

		return Arr::first($this->parameters(), fn (FunctionParameterReflection $parameter) => $nameOrIndex === $parameter->name());
	}
}
