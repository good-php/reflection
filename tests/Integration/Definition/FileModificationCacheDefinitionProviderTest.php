<?php

namespace Tests\Integration\Definition;

use GoodPhp\Reflection\Cache\Verified\VerifiedCache;
use GoodPhp\Reflection\NativePHPDoc\Definition\Cache\FileModificationCacheDefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\DefinitionProvider;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\ClassTypeDefinition;
use GoodPhp\Reflection\NativePHPDoc\Definition\TypeDefinition\UsedTraitsDefinition;
use Phake;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Tests\Integration\IntegrationTestCase;
use Tests\Stubs\Classes\ClassStub;

class FileModificationCacheDefinitionProviderTest extends IntegrationTestCase
{
	private DefinitionProvider $delegate;

	private FileModificationCacheDefinitionProvider $definitionProvider;

	protected function setUp(): void
	{
		parent::setUp();

		$this->delegate = Phake::mock(DefinitionProvider::class);
		Phake::when($this->delegate)
			->forType(Phake::anyParameters())
			->thenReturn(null);
		Phake::when($this->delegate)
			->forType(ClassStub::class)
			->thenReturn(new ClassTypeDefinition(
				qualifiedName: ClassStub::class,
				fileName: '/opt/project/tests/Stubs/Classes/ClassStub.php',
				builtIn: false,
				anonymous: false,
				final: true,
				abstract: false,
				readOnly: false,
				cloneable: true,
				instantiable: true,
				typeParameters: [],
				extends: null,
				implements: [],
				uses: new UsedTraitsDefinition(),
				constants: [],
				properties: [],
				methods: []
			));

		$this->definitionProvider = new FileModificationCacheDefinitionProvider(
			$this->delegate,
			new VerifiedCache(
				new Psr16Cache(new ArrayAdapter())
			)
		);
	}

	public function testProvidesDefinitionForTypeNotInCache(): void
	{
		$actual = $this->definitionProvider->forType(ClassStub::class);

		self::assertNotNull($actual);

		self::assertEquals(
			$this->delegate->forType(ClassStub::class),
			$actual
		);
	}
}
