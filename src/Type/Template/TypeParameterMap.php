<?php

namespace GoodPhp\Reflection\Type\Template;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

final class TypeParameterMap
{
	/**
	 * @param array<string, Type|null> $types
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
	 * @param iterable<int, TypeParameterDefinition> $typeParameters
	 *
	 * @return Collection<int, Type>
	 */
	public function toArguments(iterable $typeParameters): Collection
	{
		return Collection::wrap($typeParameters)
			->flatMap(function (TypeParameterDefinition $parameter) {
				$type = $this->types[$parameter->name] ?? null;

				if (!$type) {
					return [];
				}

				if ($parameter->variadic) {
					Assert::isInstanceOf($type, TupleType::class);

					return $type->types;
				}

				return [$type];
			});
	}
}
