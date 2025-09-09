<?php

namespace Benchmark\Stubs\Traits;

/**
 * @template O
 * @template U
 */
trait ParentTraitStub
{
	use TraitWithoutProperties;

	public int $prop;

	public function traitMethod(): void {}
}
