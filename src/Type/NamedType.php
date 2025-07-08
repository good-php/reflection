<?php

namespace GoodPhp\Reflection\Type;

use Webmozart\Assert\Assert;

class NamedType implements Type
{
	use TypeExtensions;

	/**
	 * @param class-string|string $name
	 * @param list<Type>          $arguments
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $arguments = [],
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

	/**
	 * @param list<Type|string>|null $arguments
	 */
	public static function wrap(string|self $name, ?array $arguments = null): self
	{
		if ($name instanceof self) {
			Assert::null($arguments, 'Arguments must be null when a NamedType instance is given.');

			return $name;
		}

		return new self(
			$name,
			array_map(
				fn (Type|string $type) => is_string($type) ? new self($type) : $type,
				$arguments ?? []
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

		$types = array_map(function (Type $type) use ($callback, &$changed) {
			$newType = $callback($type);

			if ($type !== $newType) {
				$changed = true;
			}

			return $newType;
		}, $this->arguments);

		if ($changed) {
			return new self($this->name, $types);
		}

		return $this;
	}

	public function __toString(): string
	{
		$arguments = array_map(
			fn (Type $type) => (string) $type,
			$this->arguments
		);

		return $this->name . ($arguments ? '<' . implode(', ', $arguments) . '>' : '');
	}
}
