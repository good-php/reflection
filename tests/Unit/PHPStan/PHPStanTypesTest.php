<?php

namespace Tests\Unit\PHPStan;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PHPStanTypesTest extends TypeInferenceTestCase
{
	#[DataProvider('fileAssertsProvider')]
	public function testFileAsserts(
		string $assertType,
		string $file,
		mixed ...$args
	): void {
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function fileAssertsProvider(): iterable
	{
		yield from self::gatherAssertTypesFromDirectory(__DIR__ . '/../../Types');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/../../../phpstan-extension.neon'];
	}
}
