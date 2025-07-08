<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use Illuminate\Support\Arr;

class ArrayAttributes implements Attributes
{
	/**
	 * @param array<class-string<object>, array<int, object>|callable(): array<int, object>> $attributes
	 */
	public function __construct(
		private readonly array $attributes = [],
	) {}

	/**
	 * @param class-string<object>|null $className
	 */
	public function has(?string $className = null): bool
	{
		$attributes = $className ? $this->attributes[$className] ?? [] : $this->attributes;

		return (bool) $attributes;
	}

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return ($className is null ? list<object> : list<AttributeType>)
	 */
	public function all(?string $className = null): array
	{
		/** @var list<AttributeType> */
		return $this->resolveAttributesFiltered($className);
	}

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType> $className
	 *
	 * @return AttributeType|null
	 */
	public function sole(string $className): ?object
	{
		$attributes = $this->resolveAttributesFiltered($className);

		$count = count($attributes);

		return match ($count) {
			0       => null,
			1       => $attributes[0],
			default => throw new MultipleAttributesFoundException($className),
		};
	}

	public function allEqual(Attributes $attributes): bool
	{
		$thisAttributes = $this->all();
		$otherAttributes = $attributes->all();

		if (count($thisAttributes) !== count($otherAttributes)) {
			return false;
		}

		foreach ($thisAttributes as $thisAttribute) {
			$otherAttributeKey = null;

			foreach ($otherAttributes as $otherAttributeIndex => $otherAttribute) {
				$equals = method_exists($thisAttribute, 'equals') ?
					$thisAttribute->equals($otherAttribute) :
					$thisAttribute == $otherAttribute;

				if ($equals) {
					$otherAttributeKey = $otherAttributeIndex;
				}
			}

			if ($otherAttributeKey === null) {
				return false;
			}

			unset($otherAttributes[$otherAttributeKey]);
		}

		return true;
	}

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return list<AttributeType>
	 */
	private function resolveAttributesFiltered(?string $className = null): array
	{
		$attributes = $className ? $this->attributes[$className] ?? [] : Arr::flatten($this->attributes);

		/* @phpstan-ignore return.type */
		return array_values(
			is_array($attributes) ? $attributes : $attributes()
		);
	}

	public function __toString(): string
	{
		$attributes = collect($this->attributes)
			->map(fn (mixed $attributes, string $className) => "\\{$className}(...)")
			->implode(', ');

		return "#[{$attributes}]";
	}
}
