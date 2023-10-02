<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Str;

/**
 * @template-covariant T
 *
 * @implements TypeReflection<T>
 */
abstract class NpdTypeReflection implements TypeReflection
{
	abstract public function qualifiedName(): string;

	abstract public function type(): NamedType;

	public function shortName(): string
	{
		return Str::afterLast($this->qualifiedName(), '\\');
	}

	public function location(): string
	{
		return $this->qualifiedName();
	}

	public function __toString(): string
	{
		return $this->shortName();
	}
}