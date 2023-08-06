<?php

namespace GoodPhp\Reflection\Reflector\Reflection\Attributes;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

class HasNativeAttributes implements HasAttributes
{
	/** @var Lazy<Collection<int, object>> */
	private Lazy $attributes;

	/**
	 * @param callable(): list<ReflectionAttribute<object>> $nativeAttributes
	 */
	public function __construct(callable $nativeAttributes)
	{
		$this->attributes = lazy(
			fn () => Collection::make($nativeAttributes())->map(fn (ReflectionAttribute $nativeAttribute) => $nativeAttribute->newInstance())
		);
	}

	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection
	{
		return $this->attributes->value();
	}
}
