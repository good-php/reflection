<?php

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\EnumTypeDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use GoodPhp\Reflection\Reflector\Reflector;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use Illuminate\Support\Collection;
use ReflectionEnum;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant T
 *
 * @extends TypeReflection<T>
 */
class EnumReflection extends TypeReflection implements HasAttributes
{
	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $declaredMethods;

	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $methods;

	private readonly ReflectionEnum $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	public function __construct(
		private readonly EnumTypeDefinition $definition,
		private readonly Reflector $reflector
	) {
		$this->declaredMethods = lazy(
			fn () => $this->definition
				->methods
				->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, TypeParameterMap::empty()))
		);
		$this->methods = lazy(
			fn () => collect([
				...$this->implements(),
				...$this->uses(),
			])
				->filter()
				->flatMap(function (Type $type) {
					$reflection = $this->reflector->forNamedType($type);

					return match (true) {
						$reflection instanceof ClassReflection,
							$reflection instanceof InterfaceReflection,
							$reflection instanceof TraitReflection => $reflection->methods(),
						default                                 => [],
					};
				})
				->concat($this->declaredMethods())
				->keyBy(fn (MethodReflection $method) => $method->name())
				->values()
		);

		$this->nativeReflection = new ReflectionEnum($this->definition->qualifiedName);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function implements(): Collection
	{
		return $this->definition->implements;
	}

	/**
	 * @return Collection<int, Type>
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
		return $this->declaredMethods->value();
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function methods(): Collection
	{
		return $this->methods->value();
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}
}
