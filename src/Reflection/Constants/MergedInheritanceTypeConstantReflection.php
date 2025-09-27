<?php

namespace GoodPhp\Reflection\Reflection\Constants;

use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 * Generally, constant reflection wouldn't need to be merged, as technically the "topmost" constant
 * takes priority, and supertypes' definitions of the same constant are ignored completely.
 *
 * However, PHPDoc types are inherited from supertypes. This, unfortunately, means
 * we have to keep track of all constants and merge their types as good we can.
 *
 * @template-contravariant ReflectableType of object
 *
 * @implements TypeConstantReflection<ReflectableType>
 */
final class MergedInheritanceTypeConstantReflection implements TypeConstantReflection
{
	/** @var TypeConstantReflection<ReflectableType> */
	private TypeConstantReflection $typeFromReflection;

	/**
	 * @param list<TypeConstantReflection<ReflectableType>> $reflections
	 */
	private function __construct(
		private readonly array $reflections,
	) {}

	/**
	 * @template ReflectableTypeScoped of object
	 *
	 * @param list<TypeConstantReflection<ReflectableTypeScoped>> $reflections
	 *
	 * @return TypeConstantReflection<ReflectableTypeScoped>
	 */
	public static function merge(array $reflections): TypeConstantReflection
	{
		Assert::notEmpty($reflections);

		if (count($reflections) === 1) {
			return $reflections[0];
		}

		return new self($reflections);
	}

	public function attributes(): Attributes
	{
		return $this->reflections[0]->attributes();
	}

	public function withStaticType(NamedType $staticType): static
	{
		return new self(
			array_map(fn (TypeConstantReflection $reflection) => $reflection->withStaticType($staticType), $this->reflections),
		);
	}

	public function name(): string
	{
		return $this->reflections[0]->name();
	}

	public function type(): ?Type
	{
		return $this->typeFromReflection()->type();
	}

	public function typeSource(): ?TypeSource
	{
		return $this->typeFromReflection()->typeSource();
	}

	public function isFinal(): bool
	{
		return $this->reflections[0]->isFinal();
	}

	public function value(): mixed
	{
		return $this->reflections[0]->value();
	}

	/**
	 * @return HasConstants<ReflectableType>
	 */
	public function declaringType(): HasConstants
	{
		return $this->reflections[0]->declaringType();
	}

	/**
	 * @return TypeConstantReflection<ReflectableType>
	 */
	private function typeFromReflection(): TypeConstantReflection
	{
		if (isset($this->typeFromReflection)) {
			return $this->typeFromReflection;
		}

		// First @var in the inheritance tree - overwrites the native typehint
		$firstConstantWithPhpDocVar = Arr::first($this->reflections, fn (TypeConstantReflection $reflection) => $reflection->typeSource() === TypeSource::PHP_DOC);

		return $this->typeFromReflection = $firstConstantWithPhpDocVar ?? $this->reflections[0];
	}

	public function __toString(): string
	{
		return (string) $this->reflections[0];
	}
}
