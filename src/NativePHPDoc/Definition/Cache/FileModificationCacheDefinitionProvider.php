<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\Cache;

use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use RuntimeException;
use Throwable;

class FileModificationCacheDefinitionProvider implements DefinitionProvider
{
	public function __construct(
		private readonly DefinitionProvider $delegate,
		private readonly VerifiedCache $verifiedCache
	) {}

	public function forType(string $type): ?TypeDefinition
	{
		return $this->verifiedCache->remember(
			'type.' . CacheUtils::normalizeTypeName($type),
			fn (TypeDefinition $definition) => $definition->fileName ? (string) $this->fileModificationTime($definition->fileName) : null,
			fn ()                           => $this->delegate->forType($type),
		);
	}

	private function fileModificationTime(string $fileName): int
	{
		try {
			$variableKey = filemtime($fileName);

			if ($variableKey === false) {
				throw new RuntimeException();
			}
		} catch (Throwable) {
			$variableKey = time();
		}

		return $variableKey;
	}
}
