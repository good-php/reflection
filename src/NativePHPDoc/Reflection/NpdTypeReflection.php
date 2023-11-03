<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection;

use GoodPhp\Reflection\Type\NamedType;
use Illuminate\Support\Str;

abstract class NpdTypeReflection
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
