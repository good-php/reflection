<?php

namespace GoodPhp\Reflection\NativePHPDoc\Definition\NativePHPDoc\PhpDoc;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\UsesTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;

class ParsedPhpDoc
{
	/** @var list<PhpDocChildNode> */
	private readonly array $childrenByPrecedence;

	public function __construct(
		public readonly PhpDocNode $phpDocNode,
	) {
		$childrenByPrecedence = $this->phpDocNode->children;

		usort($childrenByPrecedence, function (PhpDocChildNode $a, PhpDocChildNode $b) {
			$aIsSpecificTag = $a instanceof PhpDocTagNode && Str::startsWith($a->name, ['@phpstan-', '@psalm-']);
			$bIsSpecificTag = $b instanceof PhpDocTagNode && Str::startsWith($b->name, ['@phpstan-', '@psalm-']);

			if ($aIsSpecificTag && !$bIsSpecificTag) {
				return -1;
			}

			if ($bIsSpecificTag && !$aIsSpecificTag) {
				return 1;
			}

			return 0;
		});

		$this->childrenByPrecedence = $childrenByPrecedence;
	}

	public static function empty(): self
	{
		/** @var self|null $instance */
		static $instance;

		if (!$instance) {
			$instance = new self(new PhpDocNode([]));
		}

		return $instance;
	}

	/**
	 * @return list<PhpDocTagNode>
	 */
	public function templateTags(): array
	{
		return array_values(array_filter(
			$this->childrenByPrecedence,
			fn (PhpDocChildNode $node) => $node instanceof PhpDocTagNode && $node->value instanceof TemplateTagValueNode
		));
	}

	public function firstExtendsTagValue(callable $predicate): ?ExtendsTagValueNode
	{
		return Arr::first(
			$this->tagValuesOfType(ExtendsTagValueNode::class),
			$predicate,
		);
	}

	public function firstImplementsTagValue(callable $predicate): ?ImplementsTagValueNode
	{
		return Arr::first(
			$this->tagValuesOfType(ImplementsTagValueNode::class),
			$predicate,
		);
	}

	public function firstUsesTagValue(callable $predicate): ?UsesTagValueNode
	{
		return Arr::first(
			$this->tagValuesOfType(UsesTagValueNode::class),
			$predicate,
		);
	}

	public function firstVarTagValue(): ?VarTagValueNode
	{
		return Arr::first(
			$this->tagValuesOfType(VarTagValueNode::class),
		);
	}

	public function firstParamTagValue(string $parameterName): ?ParamTagValueNode
	{
		return Arr::first(
			$this->tagValuesOfType(ParamTagValueNode::class),
			fn (ParamTagValueNode $node) => $parameterName === Str::after($node->parameterName, '$')
		);
	}

	public function firstReturnTagValue(): ?ReturnTagValueNode
	{
		return Arr::first(
			$this->tagValuesOfType(ReturnTagValueNode::class),
		);
	}

	/**
	 * @template TTagValueType of PhpDocTagValueNode
	 *
	 * @param class-string<TTagValueType> $tagValueType
	 *
	 * @return list<TTagValueType>
	 */
	public function tagValuesOfType(string $tagValueType): array
	{
		$result = [];

		foreach ($this->childrenByPrecedence as $node) {
			if (!$node instanceof PhpDocTagNode || !$node->value instanceof $tagValueType) {
				continue;
			}

			$result[] = $node->value;
		}

		return $result;
	}
}
