<?php

namespace Tests\Types;

use Carbon\Month;
use Exception;
use GoodPhp\Reflection\Reflection\ClassReflection;
use GoodPhp\Reflection\Reflection\EnumReflection;
use GoodPhp\Reflection\Reflection\InterfaceReflection;
use GoodPhp\Reflection\Reflection\SpecialTypeReflection;
use GoodPhp\Reflection\Reflection\TraitReflection;
use GoodPhp\Reflection\Reflection\TypeReflection;
use GoodPhp\Reflection\Reflector;
use IteratorAggregate;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPUnit\Architecture\Enums\Visibility;
use Symfony\Component\Console\Output\AnsiColorMode;

use function PHPStan\Testing\assertSuperType;
use function PHPStan\Testing\assertType;

/** @var Reflector $reflector */

// Constant class-string
assertType(ClassReflection::class . '<Exception>', $reflector->forType(Exception::class));
assertType(InterfaceReflection::class . '<IteratorAggregate>', $reflector->forType(IteratorAggregate::class));
assertType(TraitReflection::class . '<PHPStan\Type\Traits\ObjectTypeTrait>', $reflector->forType(ObjectTypeTrait::class));
assertType(EnumReflection::class . '<PHPUnit\Architecture\Enums\Visibility, string>', $reflector->forType(Visibility::class));
assertType(EnumReflection::class . '<Carbon\Month, int>', $reflector->forType(Month::class));
assertType(EnumReflection::class . '<Symfony\Component\Console\Output\AnsiColorMode, null>', $reflector->forType(AnsiColorMode::class));

// Through ::class
/** @var IteratorAggregate $iterator */
assertType(InterfaceReflection::class . '<IteratorAggregate>', $reflector->forType($iterator::class));

// Anonymous class. It's class name is random, so we can't use assertType()
assertSuperType(ClassReflection::class, $reflector->forType(new class () {}));

// Special types
assertType(SpecialTypeReflection::class . '<object>', $reflector->forType('object'));
assertType(SpecialTypeReflection::class . '<string>', $reflector->forType('string'));
assertType(SpecialTypeReflection::class . '<int>', $reflector->forType('int'));
assertType(SpecialTypeReflection::class . '<float>', $reflector->forType('float'));
assertType(SpecialTypeReflection::class . '<bool>', $reflector->forType('bool'));
assertType(SpecialTypeReflection::class . '<iterable>', $reflector->forType('iterable'));
assertType(SpecialTypeReflection::class . '<array>', $reflector->forType('array'));
assertType(SpecialTypeReflection::class . '<callable(): mixed>', $reflector->forType('callable'));

// Can't infer
assertType(TypeReflection::class . '<mixed>', $reflector->forType('unknown'));
assertType(TypeReflection::class . '<mixed>', $reflector->forType(123));
assertType(TypeReflection::class . '<mixed>', $reflector->forType());
