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

	public function function(string $i): mixed
	{
		// TODO: Implement function() method.
	}

	case FIRST;
	case SECOND;
}
