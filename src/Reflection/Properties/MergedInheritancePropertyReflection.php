<?php

namespace GoodPhp\Reflection\Reflection\Properties;

use GoodPhp\Reflection\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflection\FunctionParameterReflection;
use GoodPhp\Reflection\Reflection\PropertyReflection;
use GoodPhp\Reflection\Reflection\TypeSource;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 * Generally, property reflection wouldn't need to be merged, as technically the "topmost" property
 * takes priority, and supertypes' definitions of the same property are ignored completely.
 *
 * However, PHPDoc types are inherited from supertypes. This, unfortunately, means
 * we have to keep track of all properties and merge their types as good we can.
 *
 * @template-contravariant ReflectableType of object
 *
 * @implements PropertyReflection<ReflectableType>
 */
final class MergedInheritancePropertyReflection implements PropertyReflection
{
	/** @var PropertyReflection<ReflectableType> */
	private PropertyReflection $typeFromReflection;

	/**
	 * @param list<PropertyReflection<ReflectableType>> $reflections
	 */
	private function __construct(
		private readonly array $reflections,
	) {}

	/**
	 * @template ReflectableTypeScoped of object
	 *
	 * @param list<PropertyReflection<ReflectableTypeScoped>> $reflections
	 *
	 * @return PropertyReflection<ReflectableTypeScoped>
	 */
	public static function merge(array $reflections): PropertyReflection
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
			array_map(fn (PropertyReflection $reflection) => $reflection->withStaticType($staticType), $this->reflections),
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

	public function hasDefaultValue(): bool
	{
		return $this->reflections[0]->hasDefaultValue();
	}

	public function defaultValue(): mixed
	{
		return $this->reflections[0]->defaultValue();
	}

	public function isPromoted(): bool
	{
		return $this->reflections[0]->isPromoted();
	}

	public function promotedParameter(): ?FunctionParameterReflection
	{
		return $this->reflections[0]->promotedParameter();
	}

	public function get(object $receiver): mixed
	{
		return $this->reflections[0]->get($receiver);
	}

	public function set(object $receiver, mixed $value): void
	{
		$this->reflections[0]->set($receiver, $value);
	}

	public function setLax(object $receiver, mixed $value): void
	{
		$this->reflections[0]->setLax($receiver, $value);
	}

	public function location(): string
	{
		return $this->reflections[0]->location();
	}

	/**
	 * @return HasProperties<ReflectableType>
	 */
	public function declaringType(): HasProperties
	{
		return $this->reflections[0]->declaringType();
	}

	/**
	 * @return PropertyReflection<ReflectableType>
	 */
	private function typeFromReflection(): PropertyReflection
	{
		if (isset($this->typeFromReflection)) {
			return $this->typeFromReflection;
		}

		// First @var in the inheritance tree - overwrites the native typehint
		$firstPropertyWithPhpDocVar = Arr::first($this->reflections, fn (PropertyReflection $reflection) => $reflection->typeSource() === TypeSource::PHP_DOC);

		return $this->typeFromReflection = $firstPropertyWithPhpDocVar ?? $this->reflections[0];
	}

	public function __toString(): string
	{
		return (string) $this->reflections[0];
	}
}
