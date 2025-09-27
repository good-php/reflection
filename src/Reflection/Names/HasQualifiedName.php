<?php

namespace GoodPhp\Reflection\Reflection\Names;

interface HasQualifiedName
{
	public function fileName(): ?string;

	public function qualifiedName(): string;

	public function shortName(): string;
}
