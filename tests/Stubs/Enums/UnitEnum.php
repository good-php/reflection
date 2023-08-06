<?php

namespace Tests\Stubs\Enums;

use Tests\Stubs\Interfaces\NonGenericInterface;
use Tests\Stubs\Traits\TraitWithoutProperties;

enum UnitEnum implements NonGenericInterface
{
	use TraitWithoutProperties;
	use TraitWithoutProperties {
		otherFunction as otherOtherFunction;
	}

	case FIRST;
	case SECOND;

	public function function(string $i): mixed
	{
		// TODO: Implement function() method.
	}
}
