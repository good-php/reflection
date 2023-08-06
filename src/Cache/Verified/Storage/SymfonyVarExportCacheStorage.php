<?php

namespace GoodPhp\Reflection\Cache\Verified\Storage;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\VarExporter\VarExporter;

final class SymfonyVarExportCacheStorage implements CacheStorage
{
	public function __construct(
		private readonly string $directory
	) {}

	public function get(string $key): mixed
	{
		return (function (string $key) {
			[,, $filePath] = $this->getFilePaths($key);

			if (!is_file($filePath)) {
				return null;
			}

			return require $filePath;
		})($key);
	}

	public function set(string $key, mixed $data): void
	{
		[$firstDirectory, $secondDirectory, $path] = $this->getFilePaths($key);
		$this->makeDir($this->directory);
		$this->makeDir($firstDirectory);
		$this->makeDir($secondDirectory);

		$tmpPath = sprintf('%s/%s.tmp', $this->directory, Str::random());
		$exported = VarExporter::export($data);

		file_put_contents(
			$tmpPath,
			sprintf(
				"<?php declare(strict_types = 1);\n\nreturn %s;",
				$exported
			)
		);

		$renameSuccess = @rename($tmpPath, $path);

		if ($renameSuccess) {
			return;
		}

		@unlink($tmpPath);

		if (\DIRECTORY_SEPARATOR === '/' || !file_exists($path)) {
			throw new InvalidArgumentException(sprintf('Could not write data to cache file %s.', $path));
		}
	}

	public function remove(string $key): void
	{
		[,, $filePath] = $this->getFilePaths($key);

		@unlink($filePath);
	}

	private function makeDir(string $directory): void
	{
		if (is_dir($directory)) {
			return;
		}

		$result = @mkdir($directory, 0777);

		if ($result === false) {
			clearstatcache();

			if (is_dir($directory)) {
				return;
			}

			$error = error_get_last();

			throw new InvalidArgumentException(sprintf('Failed to create directory "%s" (%s).', $this->directory, $error !== null ? $error['message'] : 'unknown cause'));
		}
	}

	/**
	 * @return array{string, string, string}
	 */
	private function getFilePaths(string $key): array
	{
		$keyHash = sha1($key);
		$firstDirectory = sprintf('%s/%s', $this->directory, mb_substr($keyHash, 0, 2));
		$secondDirectory = sprintf('%s/%s', $firstDirectory, mb_substr($keyHash, 2, 2));
		$filePath = sprintf('%s/%s.php', $secondDirectory, $keyHash);

		return [
			$firstDirectory,
			$secondDirectory,
			$filePath,
		];
	}
}
