<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\Type\NamedType;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;
use ReflectionEnum;
use Webmozart\Assert\Assert;

/**
 * @template-covariant T of \UnitEnum
 *
 * @extends TypeReflection<T>
 */
final class EnumReflection extends TypeReflection implements HasAttributes
{
	private readonly NamedType $type;

	private NamedType $staticType;

	private readonly ReflectionEnum $nativeReflection;

	private readonly Attributes $attributes;

	/** @var Collection<int, MethodReflection<$this>> */
	private readonly Collection $declaredMethods;

	/** @var Collection<int, MethodReflection<$this|TraitReflection<object>|InterfaceReflection<object>>> */
	private readonly Collection $methods;

	/**
	 * @param EnumTypeDefinition<T> $definition
	 */
	public function __construct(
		private readonly EnumTypeDefinition $definition,
		private readonly Reflector $reflector
	) {
		$this->type = new NamedType($this->qualifiedName());
		$this->staticType = $this->type;
	}

	public function withStaticType(NamedType $staticType): static
	{
		if ($this->staticType->equals($staticType)) {
			return $this;
		}

		$that = clone $this;
		$that->staticType = $staticType;
		unset($that->declaredMethods, $that->methods);

		return $that;
	}

	public function type(): NamedType
	{
		return $this->type;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	public function attributes(): Attributes
	{
		return $this->attributes ??= new Attributes(
			fn () => $this->nativeReflection()->getAttributes()
		);
	}

	/**
	 * @return Collection<int, NamedType>
	 */
	public function implements(): Collection
	{
		return $this->definition->implements;
	}

	/**
	 * @return Collection<int, NamedType>
	 */
	public function uses(): Collection
	{
		return $this->definition->uses;
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function declaredMethods(): Collection
	{
		return $this->declaredMethods ??= $this->definition
			->methods
			->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, $this->staticType, TypeParameterMap::empty()));
	}

	/**
	 * @return Collection<int, MethodReflection<$this|TraitReflection<object>|InterfaceReflection<object>>>
	 */
	public function methods(): Collection
	{
		if (isset($this->methods)) {
			return $this->methods;
		}

		$inheritedMethods = collect([
			...$this->implements(),
			...$this->uses(),
		])
			->filter()
			->flatMap(function (NamedType $type) {
				$reflection = $this->reflector->forNamedType($type);

				Assert::isInstanceOfAny($reflection, [InterfaceReflection::class, TraitReflection::class]);
				/** @var InterfaceReflection<object>|TraitReflection<object> $reflection */

				return $reflection
					->withStaticType($this->staticType)
					->methods();
			});

		/* @phpstan-ignore-next-line return.type, assign.propertyType */
		return $this->methods ??= collect([...$inheritedMethods, ...$this->declaredMethods()])
			->keyBy(fn (MethodReflection $method) => $method->name())
			->values()
			->map(fn (MethodReflection $method) => $method->withStaticType($this->staticType));
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}

	private function nativeReflection(): ReflectionEnum
	{
		return $this->nativeReflection ??= new ReflectionEnum($this->definition->qualifiedName);
	}
}
