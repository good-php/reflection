<?php

namespace Benchmark\Stubs\Enums;

use Benchmark\Stubs\Interfaces\SingleGenericInterface;

/**
 * @implements SingleGenericInterface<string>
 */
enum BackedEnum: string implements SingleGenericInterface
{
	case FIRST = 'first';
	case SECOND = 'second';
}
