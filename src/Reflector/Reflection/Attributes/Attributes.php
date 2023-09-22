<?php

namespace GoodPhp\Reflection\Reflector\Reflection\Attributes;

use Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;
use ReflectionAttribute;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

class Attributes
{
	/** @var Lazy<Collection<int, ReflectionAttribute>> */
	private readonly Lazy $attributes;

	/**
	 * @param callable(): ReflectionAttribute[]|null $makeAttributes
	 */
	public function __construct(callable $makeAttributes = null)
	{
		$makeAttributes ??= fn () => [];

		$this->attributes = lazy(fn () => collect($makeAttributes()));
	}

	/**
	 * @param class-string<Attribute> $className
	 */
	public function has(string $className): bool
	{
		return $this->attributes
			->value()
			->contains(self::matchesFilter($className));
	}

	/**
	 * @template AttributeType of \Attribute
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return Collection<int, AttributeType>
	 */
	public function all(string $className = null): Collection
	{
		return $this->attributes
			->value()
			->when(
				$className !== null,
				fn (Collection $attributes) => $attributes
					->filter(self::matchesFilter($className))
					->values()
			)
			->map(fn (ReflectionAttribute $attribute) => $attribute->newInstance());
	}

	/**
	 * @template AttributeType of \Attribute
	 *
	 * @param class-string<AttributeType> $className
	 *
	 * @return AttributeType|null
	 */
	public function sole(string $className): ?object
	{
		try {
			$attribute = $this->attributes
				->value()
				->sole(self::matchesFilter($className));

			return $attribute->newInstance();
		} catch (MultipleItemsFoundException) {
			throw new MultipleAttributesFoundException($className);
		} catch (ItemNotFoundException) {
			return null;
		}
	}

	private static function matchesFilter(?string $className): callable
	{
		return fn (ReflectionAttribute $attribute) => $className === $attribute->getName();
	}
}
