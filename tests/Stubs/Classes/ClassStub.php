<?php

namespace Tests\Stubs\Classes;

use DateTime;
use Illuminate\Support\Collection;
use Tests\Stubs\AttributeStub;
use Tests\Stubs\Interfaces\ParentInterfaceStub;
use Tests\Stubs\Interfaces\SingleTemplateType;
use Tests\Stubs\Traits\ParentTraitStub;

/**
 * @template T
 *
 * @template-covariant S of int
 *
 * @extends ParentClassStub<T, SomeStub>
 *
 * @implements ParentInterfaceStub<S, SomeStub>
 */
#[AttributeStub(something: '123')]
final class ClassStub extends ParentClassStub implements ParentInterfaceStub
{
	/** @use ParentTraitStub<T, SomeStub> */
	use ParentTraitStub {
		traitMethod as private;
		traitMethod as protected traitMethodTwo;
	}
	use ParentTraitStub;

	/** @var SomeStub[] */
	#[AttributeStub('4')]
	private array $factories;

	/** @var DoubleTemplateType<DateTime, T> */
	private DoubleTemplateType $generic;

	/**
	 * @param T $promoted
	 */
	public function __construct(
		#[AttributeStub('6')]
		public readonly mixed $promoted,
	)
	{
	}

	/**
	 * @template G
	 *
	 * @param DoubleTemplateType<SomeStub, T> $param
	 *
	 * @return Collection<S, G>
	 */
	#[AttributeStub('5')]
	public function method(
		#[AttributeStub('6')] DoubleTemplateType $param
	): Collection
	{
	}

	/**
	 * @template KValue
	 * @template K of SingleTemplateType<KValue>
	 *
	 * @param K $param
	 *
	 * @return KValue
	 */
	public function methodTwo(mixed $param): mixed
	{
	}

	/**
	 * @param parent<int, int> $parent
	 */
	public function self(parent $parent): static
	{
	}
}
