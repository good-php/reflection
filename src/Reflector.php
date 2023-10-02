<?php

namespace GoodPhp\Reflection;

use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeComparator;
use Illuminate\Support\Collection;

interface Reflector
{
	public function typeComparator(): TypeComparator;

	/**
	 * @return TypeReflection<mixed>
	 */
	public function forNamedType(NamedType $type): TypeReflection;

	/**
	 * @param Collection<int, Type> $arguments
	 *
	 * @return TypeReflection<mixed>
	 */
	public function forType(string $name, Collection $arguments = new Collection()): TypeReflection;
}
