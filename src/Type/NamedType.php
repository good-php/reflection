<?php

namespace GoodPhp\Reflection\Type;

use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

/**
 * @template-covariant T
 */
class NamedType implements Type
{
	use TypeExtensions;

	/**
	 * @param class-string<T>       $name
	 * @param Collection<int, Type> $arguments
	 */
	public function __construct(
		public readonly string $name,
		public readonly Collection $arguments = new Collection(),
	) {
		Assert::false(
			in_array($name, [
				'mixed',
				'void',
				'never',
				'null',
				'true',
				'false',
				'static',
				'self',
				'parent',
			], true)
		);
	}

	public static function wrap(string|self $name, array|Collection $arguments = null): self
	{
		if ($name instanceof self) {
			Assert::null($arguments, 'Arguments must be null when a NamedType instance is given.');

			return $name;
		}

		return new NamedType(
			$name,
			Collection::wrap($arguments ?? [])->map(
				fn (Type|string $type) => is_string($type) ? new NamedType($type) : $type
			)
		);
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->name === $this->name &&
			TypeUtil::allEqual($other->arguments, $this->arguments);
	}

	public function traverse(callable $callback): Type
	{
		$changed = false;

		$types = $this->arguments
			->map(function (Type $type) use ($callback, &$changed) {
				$newType = $callback($type);

				if ($type !== $newType) {
					$changed = true;
				}

				return $newType;
			});

		if ($changed) {
			return new self($this->name, $types);
		}

		return $this;
	}

	public function __toString(): string
	{
		$arguments = $this->arguments->join(', ');

		return $this->name . ($arguments ? '<' . $arguments . '>' : '');
	}
}