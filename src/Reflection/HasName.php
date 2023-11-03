<?php

namespace GoodPhp\Reflection\Reflection;

interface HasName
{
	public function fileName(): ?string;

	public function qualifiedName(): string;

	public function shortName(): string;

	public function location(): string;
}
