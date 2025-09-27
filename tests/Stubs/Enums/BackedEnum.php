<?php

namespace Tests\Stubs\Enums;

use Tests\Stubs\Interfaces\SingleGenericInterface;

/**
 * @implements SingleGenericInterface<string>
 */
enum BackedEnum: string implements SingleGenericInterface
{
	public const ALIASED = self::SECOND;

	case FIRST = 'first';
	case SECOND = 'second';
}
