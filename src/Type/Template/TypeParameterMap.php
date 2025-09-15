<?php

namespace GoodPhp\Reflection\Type\Template;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
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
	 * @param list<Type>                        $arguments
	 * @param iterable<TypeParameterDefinition> $typeParameters
	 */
	public static function fromArguments(array $arguments, iterable $typeParameters): self
	{
		if (!$typeParameters) {
			return self::empty();
		}

		$map = [];
		$i = 0;

		foreach ($typeParameters as $parameter) {
			if ($parameter->variadic) {
				$map[$parameter->name] = new TupleType(array_slice($arguments, $i));

				break;
			}

			$argument = $arguments[$i] ?? $parameter->default;

			if (!$argument) {
				continue;
			}

			$map[$parameter->name] = $argument;
			$i++;
		}

		return new self($map);
	}

	public static function empty(): self
	{
		/** @var self $map */
		static $map = new self([]);

		return $map;
	}

	/**
	 * @param list<TypeParameterDefinition> $typeParameters
	 *
	 * @return list<Type>
	 */
	public function toArguments(array $typeParameters): array
	{
		if (!$this->types) {
			return [];
		}

		return Arr::flatten(
			array_map(function (TypeParameterDefinition $parameter) {
				$type = $this->types[$parameter->name] ?? null;

				if (!$type) {
					return [];
				}

				if ($parameter->variadic) {
					Assert::isInstanceOf($type, TupleType::class);

					return $type->types;
				}

				return [$type];
			}, $typeParameters),
			depth: 1
		);
	}
}
