<?php

namespace Tests\Stubs\Traits;

/**
 * Parent trait description
 *
 * @template O
 * @template U
 */
trait ParentTraitStub
{
	use TraitWithoutProperties;

	public const CONSTANT = 123;

	public int $prop;

	public function traitMethod(): void {}
}
