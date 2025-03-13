<?php

namespace GoodPhp\Reflection\Type;

class TypeUtil
{
	/**
	 * @param list<Type> $types
	 * @param list<Type> $otherTypes
	 */
	public static function allEqual(array $types, array $otherTypes): bool
	{
		if (count($types) !== count($otherTypes)) {
			return false;
		}

		foreach ($types as $i => $type) {
			if (!$type->equals($otherTypes[$i])) {
				return false;
			}
		}

		return true;
	}
}
