<?php

namespace GoodPhp\Reflection\PHPStan;

use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\SpecialTypeReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflector;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use RuntimeException;

class ReflectorReturnType implements DynamicMethodReturnTypeExtension
{
	private const SPECIAL_TYPES = [
		'object',
		'string',
		'int',
		'float',
		'bool',
		'iterable',
		'array',
		'callable',
	];

	public function getClass(): string
	{
		return Reflector::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'forType';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): ?Type {
		$reflectableType = $this->typeFromMethodCall($methodCall, $scope);

		if (!$reflectableType) {
			return null;
		}

		$reflectionTypeClassName = $this->reflectionTypeClassName($reflectableType);

		if (!$reflectionTypeClassName) {
			return null;
		}

		return new GenericObjectType($reflectionTypeClassName, [$reflectableType]);
	}

	private function reflectionTypeClassName(Type $type): ?string
	{
		if (!$type->getObjectClassNames()) {
			return SpecialTypeReflection::class;
		}

		/** @var TypeWithClassName $type */
		/** @phpstan-ignore phpstanApi.varTagAssumption */
		$reflection = $type->getClassReflection();

		if (!$reflection) {
			return null;
		}

		return match (true) {
			$reflection->isClass()     => ClassReflection::class,
			$reflection->isInterface() => InterfaceReflection::class,
			$reflection->isTrait()     => TraitReflection::class,
			$reflection->isEnum()      => EnumReflection::class,
			default                    => throw new RuntimeException('Unsupported return type')
		};
	}

	private function typeFromMethodCall(MethodCall $methodCall, Scope $scope): ?Type
	{
		$nameArg = method_exists($methodCall, 'getArg') ?
			$methodCall->getArg('name', 0) :
			$methodCall->getArgs()[0] ?? null;

		if (!$nameArg) {
			return null;
		}

		$type = $scope->getType($nameArg->value);

		// Anonymous class
		if ($type->getObjectClassNames()) {
			return $type;
		}

		// Regular ::class references
		if ($type->isClassString()->yes()) {
			$type = $type->getObjectTypeOrClassStringObjectType();

			if (!$type->getObjectClassNames()) {
				return null;
			}

			return $type;
		}

		// A couple of special cases for special types
		if ($type->isString()->yes() && $type->isConstantValue()->yes()) {
			$scalarValues = $type->getConstantScalarValues();

			if (count($scalarValues) !== 1) {
				return null;
			}

			if (!in_array($scalarValues[0], self::SPECIAL_TYPES, true)) {
				return null;
			}

			/* @phpstan-ignore phpstanApi.method */
			return ParserNodeTypeToPHPStanType::resolve(new Identifier($scalarValues[0]), null);
		}

		return null;
	}
}
