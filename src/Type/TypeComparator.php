<?php

namespace GoodPhp\Reflection\Type;

use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\SpecialTypeReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\TypeParameters\HasTypeParameters;
use GoodPhp\Reflection\Reflection\TypeParameters\TypeParameterReflection;
use GoodPhp\Reflection\Reflector;
use GoodPhp\Reflection\Type\Combinatorial\IntersectionType;
use GoodPhp\Reflection\Type\Combinatorial\TupleType;
use GoodPhp\Reflection\Type\Combinatorial\UnionType;
use GoodPhp\Reflection\Type\Special\ErrorType;
use GoodPhp\Reflection\Type\Special\MixedType;
use GoodPhp\Reflection\Type\Special\NeverType;
use GoodPhp\Reflection\Type\Special\NullableType;
use GoodPhp\Reflection\Type\Special\StaticType;
use GoodPhp\Reflection\Type\Special\VoidType;
use GoodPhp\Reflection\Type\Template\TemplateType;
use GoodPhp\Reflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\Integration\Type\TypeComparatorTest;

/**
 * @see TypeComparatorTest
 */
class TypeComparator
{
	public function __construct(
		private readonly Reflector $reflector
	) {}

	public function accepts(Type $a, Type $b): bool
	{
		return match (true) {
			$a instanceof NeverType || $b instanceof NeverType => false,
			$a instanceof ErrorType || $b instanceof ErrorType => false,
			$a instanceof VoidType                             => true,
			$b instanceof VoidType                             => false,
			$a instanceof MixedType                            => true,
			$b instanceof MixedType                            => false,
			$a instanceof IntersectionType                     => $a->types->every(fn (Type $type) => $this->accepts($type, $b)),
			$b instanceof IntersectionType                     => $b->types->some(fn (Type $type) => $this->accepts($a, $type)),
			$a instanceof UnionType                            => $a->types->some(fn (Type $type) => $this->accepts($type, $b)),
			$b instanceof UnionType                            => $b->types->every(fn (Type $type) => $this->accepts($a, $type)),
			$a instanceof NullableType                         => $this->accepts($a->innerType, $b instanceof NullableType ? $b->innerType : $b),
			$b instanceof NullableType                         => false,
			// `static` type is very much like a template type - in a sense that it should have been replaced with another
			// type by the type it's compared here. But at least we can compare it with the upper bounds.
			$a instanceof StaticType => $this->accepts($a->upperBound, $b),
			$b instanceof StaticType => $this->accepts($a, $b->upperBound),
			// TODO
			$a instanceof TemplateType                         => false,
			$b instanceof TemplateType                         => false,
			$a instanceof TupleType                            => $this->acceptsTuple($a, $b),
			$b instanceof TupleType                            => false,
			$a instanceof NamedType && $b instanceof NamedType => $this->acceptsNamed($a, $b),
			default                                            => throw new InvalidArgumentException("Unsupported types given: {$a} (" . $a::class . ") and {$b} (" . $b::class . ')')
		};
	}

	public function acceptsNamed(NamedType $a, NamedType $b): bool
	{
		// If dealing with inheritance, convert bigger type into smaller type
		// and then compare to make sure type arguments aren't messed up.
		if ($a->name !== $b->name) {
			$descendant = $this->findDescendant($b, $a->name);

			// Not a super type.
			if (!$descendant) {
				return false;
			}

			return $this->accepts($a, $descendant);
		}

		$aReflection = $this->reflector->forNamedType($a);

		$typeParameters = $aReflection instanceof HasTypeParameters ? $aReflection->typeParameters() : new Collection();

		/** @var array<array{TypeParameterReflection<ClassReflection<object>|TraitReflection<object>|TraitReflection<object>>, Type, Type}> $pairs */
		$pairs = [];
		$aArguments = clone $a->arguments;
		$bArguments = clone $b->arguments;

		foreach ($typeParameters as $i => $typeParameter) {
			if ($typeParameter->variadic()) {
				$pairs[] = [$typeParameter, new TupleType($aArguments), new TupleType($bArguments)];

				break;
			}

			/** @var Type|null $aArgument */
			$aArgument = $aArguments->shift();
			/** @var Type|null $bArgument */
			$bArgument = $bArguments->shift();

			if (!$aArgument || !$bArgument) {
				throw new InvalidArgumentException('Missing type argument #' . ($i + 1) . " {$typeParameter} when comparing named types '{$a}' and '{$b}'");
			}

			$pairs[] = [$typeParameter, $aArgument, $bArgument];
		}

		foreach ($pairs as [$typeParameter, $aArgument, $bArgument]) {
			$validVariance = match ($typeParameter->variance()) {
				TemplateTypeVariance::INVARIANT     => $aArgument->equals($bArgument),
				TemplateTypeVariance::COVARIANT     => $this->accepts($aArgument, $bArgument),
				TemplateTypeVariance::CONTRAVARIANT => $this->accepts($bArgument, $aArgument),
			};

			if (!$validVariance) {
				return false;
			}
		}

		return true;
	}

	private function acceptsTuple(TupleType $a, Type $b): bool
	{
		return $b instanceof TupleType &&
			$b->types->count() >= $a->types->count() &&
			$a->types->every(fn (Type $type, int $i) => $this->accepts($type, $b->types[$i]));
	}

	private function findDescendant(NamedType $a, string $className): ?NamedType
	{
		$aReflection = $this->reflector->forNamedType($a);

		/** @var NamedType[] $descendants */
		$descendants = match (true) {
			$aReflection instanceof ClassReflection => $aReflection
				->implements()
				->concat($aReflection->extends() ? [$aReflection->extends()] : [])
				->filter(),
			$aReflection instanceof InterfaceReflection => $aReflection
				->extends(),
			$aReflection instanceof TraitReflection => new Collection(),
			$aReflection instanceof EnumReflection  => $aReflection
				->implements(),
			$aReflection instanceof SpecialTypeReflection => $aReflection
				->superTypes(),
			default => throw new InvalidArgumentException('Unsupported type of reflection [' . $aReflection::class . '] given.'),
		};

		foreach ($descendants as $type) {
			if ($type->name === $className) {
				return $type;
			}
		}

		foreach ($descendants as $type) {
			if ($super = $this->findDescendant($type, $className)) {
				return $super;
			}
		}

		return null;
	}
}
