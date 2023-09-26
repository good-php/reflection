<?php

namespace GoodPhp\Reflection\Type;

use GoodPhp\Reflection\Type\Combinatorial\ExpandedType;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;

class TypeProjector
{
	/**
	 * @return ($type is NamedType ? NamedType : Type)
	 */
	public static function templateTypes(Type $type, TypeParameterMap $typeParameterMap): Type
	{
		$mapped = TypeTraversingMapper::map($type, static function (Type $type, callable $traverse) use ($typeParameterMap): Type {
			// todo: && !$type->isArgument()
			if ($type instanceof TemplateType) {
				$newType = $typeParameterMap->types[$type->name] ?? null;

				// If no type specified for this template type, just ignore it.
				if ($newType === null) {
					return $traverse($type);
				}

				return $newType;
			}

			return $traverse($type);
		});

		return TypeTraversingMapper::map($mapped, static function (Type $type, callable $traverse): Type {
			if ($type instanceof NamedType) {
				$changed = false;

				$arguments = $type->arguments
					->flatMap(function (Type $type) use (&$changed) {
						if ($type instanceof ExpandedType && $type->innerType instanceof TupleType) {
							$changed = true;

							return $type->innerType->types;
						}

						return [$type];
					});

				if ($changed) {
					$type = new NamedType($type->name, $arguments);
				}
			}

			return $traverse($type);
		});
	}
}
