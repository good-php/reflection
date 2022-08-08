<?php

namespace Tests\Stubs\Enums;

use Tests\Stubs\Interfaces\SingleGenericInterface;

/**
 * @implements SingleGenericInterface<string>
 */
enum BackedEnum : string implements SingleGenericInterface
{
	case FIRST = 'first';
	case SECOND = 'second';
}
