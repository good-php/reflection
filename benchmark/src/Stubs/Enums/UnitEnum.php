<?php

namespace Benchmark\Stubs\Enums;

use Benchmark\Stubs\Interfaces\NonGenericInterface;
use Benchmark\Stubs\Traits\TraitWithoutProperties;

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
