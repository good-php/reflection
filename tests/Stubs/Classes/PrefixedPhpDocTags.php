<?php

namespace Tests\Stubs\Classes;

use Tests\Stubs\Interfaces\SingleGenericInterface;

/**
 * @phpstan-template TPhpStan
 * @psalm-template-covariant TPsalmCovariant of int
 *
 * @phpstan-extends SingleTemplateTypeImpl<int>
 * @phpstan-implements SingleGenericInterface<string>
 */
class PrefixedPhpDocTags extends SingleTemplateTypeImpl implements SingleGenericInterface
{
	/**
	 * @psalm-var \stdClass
	 * @var mixed
	 */
	public mixed $property;

	/**
	 * @psalm-param bool $param1
	 * @param \DateTime $param2
	 * @phpstan-param \stdClass $param2
	 *
	 * @return mixed
	 * @phpstan-return int
	 */
	public function method($param1, $param2) {}
}
