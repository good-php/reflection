<?php

namespace GoodPhp\Reflection\Type;

use Illuminate\Support\Collection;

class TypeUtil
{
	/**
	 * @param Collection<int, Type> $types
	 * @param Collection<int, Type> $otherTypes
	 */
	public static function allEqual(Collection $types, Collection $otherTypes): bool
	{
		return $types->count() === $otherTypes->count() &&
			$types->every(fn (Type $type, int $i) => $type->equals($otherTypes[$i]));
	}
}
