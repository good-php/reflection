<?php

namespace Tests\Integration\Definition;

use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\CacheUtils;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\File\FileContextParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\Native\NativeTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\PhpDocTypeMapper;
use GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc\TypeAliasResolver;
use Illuminate\Support\Str;
use Kcs\ClassFinder\Finder\ComposerFinder;
use PhpParser\Lexer\Emulative;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\Integration\IntegrationTestCase;

/**
 * @see NativePHPDocDefinitionProvider
 */
class NativePHPDocDefinitionProviderTest extends IntegrationTestCase
{
	private NativePHPDocDefinitionProvider $definitionProvider;

	protected function setUp(): void
	{
		parent::setUp();

		$parserConfig = new ParserConfig(usedAttributes: []);

		$this->definitionProvider = new NativePHPDocDefinitionProvider(
			new PhpDocStringParser(
				new Lexer($parserConfig),
				new PhpDocParser(
					$parserConfig,
					new TypeParser($parserConfig, $constExprParser = new ConstExprParser($parserConfig)),
					$constExprParser,
				),
			),
			new FileContextParser(
				new Php7(new Emulative()),
			),
			new TypeAliasResolver(),
			new NativeTypeMapper(),
			new PhpDocTypeMapper(
				new TypeAliasResolver()
			),
		);
	}

	public static function providesDefinitionForTypeProvider(): iterable
	{
		$namespace = 'Tests\Stubs';
		$reflections = (new ComposerFinder())->inNamespace($namespace);

		foreach ($reflections as $reflection) {
			/** @var ReflectionClass<object> $reflection */
			$className = $reflection->getName();
			$shortName = Str::after($className, "{$namespace}\\");

			yield $shortName => [
				$className,
				__DIR__ . '/NativePHPDocDefinitionProvider/' . CacheUtils::normalizeTypeName($className) . '.txt',
			];
		}
	}

	#[DataProvider('providesDefinitionForTypeProvider')]
	public function testProvidesDefinitionForType(string $className, string $snapshotFilename): void
	{
		$definition = $this->definitionProvider->forType($className);

		$this->assertSnapshotEqualsDump($snapshotFilename, $definition);
	}
}
