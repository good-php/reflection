<?php

namespace GoodPhp\Reflection\Type;

class TypeTraversingMapper
{
	/**
	 * @param callable(Type $type, callable(Type): Type $traverse): Type $callback
	 */
	private function __construct(private readonly mixed $callback) {}

	/**
	 * Map a Type recursively
	 *
	 * For every Type instance, the callback can return a new Type, and/or
	 * decide to traverse inner types or to ignore them.
	 *
	 * The following example converts constant strings to objects, while
	 * preserving unions and intersections:
	 *
	 * TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
	 *     if ($type instanceof UnionType || $type instanceof IntersectionType) {
	 *         // Traverse inner types
	 *         return $traverse($type);
	 *     }
	 *     if ($type instanceof ConstantStringType) {
	 *         // Replaces the current type, and don't traverse
	 *         return new ObjectType($type->getValue());
	 *     }
	 *     // Replaces the current type, and don't traverse
	 *     return new MixedType();
	 * });
	 *
	 * @param callable(Type $type, callable(Type): Type $traverse): Type $cb
	 */
	public static function map(Type $type, callable $cb): Type
	{
		$self = new self($cb);

		return $self->mapInternal($type);
	}

	public function mapInternal(Type $type): Type
	{
		return ($this->callback)($type, $this->traverseInternal(...));
	}

	public function traverseInternal(Type $type): Type
	{
		return $type->traverse($this->mapInternal(...));
	}
}
