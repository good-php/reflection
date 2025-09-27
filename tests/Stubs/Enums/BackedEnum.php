<?php

namespace Tests\Stubs\Enums;

use Tests\Stubs\Interfaces\SingleGenericInterface;

/**
 * Backed enum description
 *
 * @implements SingleGenericInterface<string>
 */
enum BackedEnum: string implements SingleGenericInterface
{
	/** Constant description */
	public const ALIASED = self::SECOND;

	/**
	 * Enum case description
	 *
	 * Multiline
	 */
	case FIRST = 'first';
	case SECOND = 'second';
}
