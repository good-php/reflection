<?php declare(strict_types=1);

namespace GoodPhp\Reflection\Reflector\Reflection;

use GoodPhp\Reflection\Definition\TypeDefinition\FunctionParameterDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\MethodDefinition;
use GoodPhp\Reflection\Definition\TypeDefinition\TypeParameterDefinition;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\Attributes;
use GoodPhp\Reflection\Reflector\Reflection\Attributes\HasAttributes;
use GoodPhp\Reflection\Type\Template\TypeParameterMap;
use GoodPhp\Reflection\Type\Type;
use GoodPhp\Reflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionMethod;
use TenantCloud\Standard\Lazy\Lazy;

use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
class MethodReflection implements HasAttributes
{
	/** @var Lazy<ReflectionMethod<object>> */
	private readonly Lazy $nativeReflection;

	/** @var Lazy<Attributes> */
	private readonly Lazy $attributes;

	/** @var Lazy<Collection<int, FunctionParameterReflection<$this>>> */
	private Lazy $parameters;

	/** @var Lazy<Type|null> */
	private Lazy $returnType;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly MethodDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->nativeReflection = lazy(fn () => new ReflectionMethod($this->owner->qualifiedName(), $this->definition->name));
		$this->attributes = lazy(fn () => new Attributes(
			fn () => $this->nativeReflection->value()->getAttributes()
		));
		$this->parameters = lazy(
			fn () => $this->definition
				->parameters
				->map(fn (FunctionParameterDefinition $parameter) => new FunctionParameterReflection($parameter, $this, $resolvedTypeParameterMap))
		);
		$this->returnType = lazy(
			fn () => $this->definition->returnType ?
				TypeProjector::templateTypes(
					$this->definition->returnType,
					$resolvedTypeParameterMap
				) :
				null
		);
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function attributes(): Attributes
	{
		return $this->attributes->value();
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	/**
	 * @return Collection<int, FunctionParameterReflection<$this>>
	 */
	public function parameters(): Collection
	{
		return $this->parameters->value();
	}

	public function returnType(): ?Type
	{
		return $this->returnType->value();
	}

	/**
	 * Call a method with strict_types=0.
	 */
	public function invoke(object $receiver, mixed ...$args): mixed
	{
		return $this->nativeReflection->value()->invoke($receiver, ...$args);
	}

	/**
	 * Call a public method with strict_types=1.
	 */
	public function invokeStrict(object $receiver, mixed ...$args): mixed
	{
		return $receiver->{$this->name()}(...$args);
	}
}
