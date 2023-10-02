<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc;

use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileClassLikeContext;
use Illuminate\Support\Str;

class TypeAliasResolver
{
	public function forComparison(string $symbol): string
	{
		// Globally referenced types should always be treated as type names.
		if (str_starts_with($symbol, '\\')) {
			return Str::after($symbol, '\\');
		}

		return mb_strtolower($symbol);
	}

	public function resolve(string $symbol, ?FileClassLikeContext $fileClassLikeContext): string
	{
		// Globally referenced types should always be treated as type names.
		if (str_starts_with($symbol, '\\')) {
			return Str::after($symbol, '\\');
		}

		$lowerSymbol = mb_strtolower($symbol);

		// There are many implicitly imported types.
		if (
			in_array($lowerSymbol, [
				'mixed', 'void', 'never', 'string', 'int', 'float', 'bool',
				'array', 'object', 'callable', 'iterable', 'null', 'true',
				'false', 'static', 'self', 'parent',
			], true)
		) {
			return $lowerSymbol;
		}

		if (!$fileClassLikeContext) {
			return $symbol;
		}

		$alias = $this->imported($symbol, $fileClassLikeContext);

		if ($alias !== null) {
			return $alias;
		}

		if ($fileClassLikeContext->namespace) {
			return "{$fileClassLikeContext->namespace}\\{$symbol}";
		}

		return $symbol;
	}

	public function imported(string $symbol, FileClassLikeContext $fileClassLikeContext): ?string
	{
		$alias = $symbol;

		$namespaceParts = explode('\\', $symbol);
		$lastPart = array_shift($namespaceParts);

		if ($lastPart) {
			$alias = mb_strtolower($lastPart);
		}

		if (!isset($fileClassLikeContext->uses[$alias])) {
			return null;
		}

		$full = $fileClassLikeContext->uses[$alias];

		if (!empty($namespaceParts)) {
			$full .= '\\' . implode('\\', $namespaceParts);
		}

		return $full;
	}
}
