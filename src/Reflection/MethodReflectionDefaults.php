<?php declare(strict_types=1);

namespace GoodPhp\Reflection\Reflection;

use Illuminate\Support\Arr;

trait MethodReflectionDefaults
{
	/**
	 * @return list<FunctionParameterReflection>
	 */
	abstract public function parameters(): array;

	public function parameter(string|int $nameOrIndex): ?FunctionParameterReflection
	{
		if (is_int($nameOrIndex)) {
			return $this->parameters()[$nameOrIndex] ?? null;
		}

		return Arr::first($this->parameters(), fn (FunctionParameterReflection $parameter) => $nameOrIndex === $parameter->name());
	}
}
