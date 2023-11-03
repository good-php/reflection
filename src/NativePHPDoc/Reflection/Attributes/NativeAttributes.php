<?php

namespace GoodPhp\Reflection\NativePHPDoc\Reflection\Attributes;

use GoodPhp\Reflection\Reflection\Attributes\ArrayAttributes;
use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

final class NativeAttributes implements Attributes
{
	/** @var Lazy<ArrayAttributes> */
	private readonly Lazy $delegate;

	/**
	 * @param callable(): ReflectionAttribute<object>[]|null $makeAttributes
	 */
	public function __construct(callable $makeAttributes = null)
	{
		$makeAttributes ??= fn () => [];

		$this->delegate = lazy(
			fn () => new ArrayAttributes(
				collect($makeAttributes())
					->groupBy(fn (ReflectionAttribute $attribute) => $attribute->getName())
					->map(
						fn (Collection $attributes) => $attributes
							->map(fn (ReflectionAttribute $attribute) => $attribute->newInstance())
							->all()
					)
					->all()
			)
		);
	}

	/**
	 * @param class-string<object>|null $className
	 */
	public function has(string $className = null): bool
	{
		return $this->delegate->value()->has($className);
	}

	/**
	 * @template AttributeType of object
	 *
	 * @param class-string<AttributeType>|null $className
	 *
	 * @return ($className is null ? Collection<int, object> : Collection<int, AttributeType>)
	 */
	public function all(string $className = null): Collection
	{
		return $this->delegate->value()->all($className);
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
		return $this->delegate->value()->sole($className);
	}

	public function allEqual(Attributes $attributes): bool
	{
		return $this->delegate->value()->allEqual($attributes);
	}

	public function __toString()
	{
		return (string) $this->delegate->value();
	}
}
