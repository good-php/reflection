<?php

namespace GoodPhp\Reflection\Definition\NativePHPDoc\Native;

use GoodPhp\Reflection\Definition\NativePHPDoc\TypeContext;
use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\PrimitiveType;
use GoodPhp\Reflection\Type\Special\ErrorType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Special\NeverType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\ParentType;
use GoodPhp\Reflection\Type\Special\StaticType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class NativeTypeMapper
{
	/**
	 * @param ReflectionType|string|iterable<int, ReflectionType|string> $type
	 *
	 * @return ($type is ReflectionType|string ? Type : Collection<int, Type>)
	 */
	public function map(ReflectionType|string|iterable $type, TypeContext $context): Type|Collection
	{
		if (is_iterable($type)) {
			return Collection::wrap($type)->map(fn (ReflectionType|string $type) => $this->map($type, $context));
		}

		$isNull = fn (ReflectionType $isNullType) => $isNullType instanceof ReflectionNamedType && $isNullType->getName() === 'null';

		$mappedType = match (true) {
			$type instanceof ReflectionIntersectionType => new IntersectionType(
				$this->map($type->getTypes(), $context)
			),
			$type instanceof ReflectionUnionType => new UnionType(
				$this->map(
					array_values(
						array_filter(
							$type->getTypes(),
							fn (ReflectionType $type) => !$isNull($type)
						)
					),
					$context
				)
			),
			$type instanceof ReflectionNamedType => $this->mapNamed($type->getName(), $context),
			is_string($type)                     => $this->mapNamed($type, $context),
			default                              => new ErrorType((string) $type),
		};

		if ($type instanceof ReflectionType && $type->allowsNull() && !($type instanceof ReflectionNamedType && $type->getName() === 'mixed')) {
			return new NullableType($mappedType);
		}

		if ($type instanceof ReflectionUnionType && Arr::first($type->getTypes(), fn (ReflectionType $type) => $isNull($type))) {
			return new NullableType($mappedType);
		}

		return $mappedType;
	}

	private function mapNamed(string $name, TypeContext $context): Type
	{
		return match (mb_strtolower($name)) {
			'mixed' => MixedType::get(),
			'never' => NeverType::get(),
			'void'  => VoidType::get(),
			'true', 'false' => PrimitiveType::boolean(),
			'self'   => $context->definingType,
			'parent' => new ParentType($context->definingType),
			'static' => new StaticType($context->definingType),
			default  => new NamedType($name),
		};
	}
}
