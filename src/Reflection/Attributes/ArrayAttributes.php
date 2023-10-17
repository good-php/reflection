<?php

namespace GoodPhp\Reflection\Reflection\Attributes;

use Attribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;
use Illuminate\Support\Str;

class ArrayAttributes implements Attributes
{
	/**
	 * @param array<class-string<object>, array<int, object>|callable(): array<int, object>>
	 */
	public function __construct(
		private readonly array $attributes = [],
	)
	{
	}

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
	 * @return Collection<int, AttributeType>
	 */
	public function all(string $className = null): Collection
	{
		/** @var Collection<int, AttributeType> */
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
		try {
			return $this->resolveAttributesFiltered($className)->sole();
		} catch (MultipleItemsFoundException) {
			throw new MultipleAttributesFoundException($className);
		} catch (ItemNotFoundException) {
			return null;
		}
	}

	public function __toString(): string
	{
		$attributes = collect($this->attributes)
			->map(fn (mixed $attributes, string $className) => "\\{$className}(...)")
			->implode(', ');

		return "#[{$attributes}]";
	}

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return Collection<int, AttributeType>
	 */
	private function resolveAttributesFiltered(?string $className = null): Collection
	{
		$attributes = $className ? $this->attributes[$className] ?? [] : Arr::flatten($this->attributes);

		return collect(
			is_array($attributes) ? $attributes : $attributes()
		);
	}
}
