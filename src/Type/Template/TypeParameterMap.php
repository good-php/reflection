<?php

namespace GoodPhp\Reflection\Type\Template;

use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;

final class TypeParameterMap
{
	/**
	 * @param array<string, Type|array<int, Type>|null> $types
	 */
	public function __construct(
		public readonly array $types
	) {}

	/**
	 * @param Type[]                            $arguments
	 * @param iterable<TypeParameterDefinition> $typeParameters
	 */
	public static function fromArguments(array $arguments, iterable $typeParameters): self
	{
		$map = [];
		$i = 0;

		foreach ($typeParameters as $parameter) {
			if ($parameter->variadic) {
				$map[$parameter->name] = new TupleType(collect(array_slice($arguments, $i)));

				break;
			}

			if (!$argument = $arguments[$i] ?? null) {
				break;
			}

			$map[$parameter->name] = $argument;
			$i++;
		}

		return new self($map);
	}

	public static function empty(): self
	{
		static $map;

		if (!$map) {
			$map = new self([]);
		}

		return $map;
	}

	/**
	 * @param iterable<TypeParameterDefinition> $typeParameters
	 *
	 * @return Collection<int, Type>
	 */
	public function toList(iterable $typeParameters): Collection
	{
		return Collection::wrap($typeParameters)
			->map(fn (TypeParameterDefinition $parameter) => $this->types[$parameter->name] ?? null);
	}
}
