<?php

namespace Tests;

use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition;
use Illuminate\Support\Str;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class TestCase extends \PHPUnit\Framework\TestCase
{
	protected function assertSnapshotEqualsDump(string $snapshotFileName, mixed $actual): void
	{
		$actual = $this->dump($actual);

		if (file_exists($snapshotFileName)) {
			self::assertSame(\Safe\file_get_contents($snapshotFileName), $actual);
		} else {
			\Safe\file_put_contents($snapshotFileName, $actual);
		}
	}

	protected function dump(mixed $data): string
	{
		$cloner = new VarCloner();
		$cloner->setMaxItems(-1);
		$cloner->addCasters([
			TypeDefinition::class => static function (TypeDefinition $definition, array $result): array {
				if ($fileName = $result['fileName'] ?? null) {
					$path = Str::after($fileName, '/tests/');

					$result['fileName'] = "/app/tests/{$path}";
				}

				return $result;
			},
		]);

		$dumper = new CliDumper(flags: AbstractDumper::DUMP_LIGHT_ARRAY | AbstractDumper::DUMP_TRAILING_COMMA | AbstractDumper::DUMP_TRAILING_COMMA);
		$dumper->setColors(false);
		$dumper->setIndentPad("\t");

		$data = $cloner->cloneVar($data)->withRefHandles(false);

		return rtrim($dumper->dump($data, true));
	}
}
