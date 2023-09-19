<?php

namespace Tests\Stubs\Classes;

class AllPhpDocTypes
{
	/**
	 * @param int                $p1
	 * @param int                $p2
	 * @param positive-int       $p3
	 * @param negative-int       $p4
	 * @param int<0, 50>         $p5
	 * @param int-mask-of<1|2|4> $p6
	 * @param int-mask           $p7
	 * @param number             $p8
	 * @param numeric            $p9
	 * @param float              $p10
	 * @param float              $p11
	 *
	 * @return void
	 */
	public function f1(
		$p1,
		$p2,
		$p3,
		$p4,
		$p5,
		$p6,
		$p7,
		$p8,
		$p9,
		$p10,
		$p11
	) {
	}

	/**
	 * @param string           $p1
	 * @param numeric-string   $p2
	 * @param literal-string   $p3
	 * @param class-string     $p4
	 * @param interface-string $p5
	 * @param trait-string     $p6
	 * @param callable-string  $p7
	 * @param non-empty-string $p8
	 *
	 * @return never
	 */
	public function f2(
		$p1,
		$p2,
		$p3,
		$p4,
		$p5,
		$p6,
		$p7,
		$p8
	) {
	}

	/**
	 * @param bool  $p1
	 * @param bool  $p2
	 * @param true  $p3
	 * @param false $p4
	 *
	 * @return never-return
	 */
	public function f3(
		$p1,
		$p2,
		$p3,
		$p4
	) {
	}

	/**
	 * @param array             $p1
	 * @param array-key         $p2
	 * @param associative-array $p3
	 * @param non-empty-array   $p4
	 * @param list              $p5
	 * @param non-empty-list    $p6
	 *
	 * @return never-returns
	 */
	public function f4(
		$p1,
		$p2,
		$p3,
		$p4,
		$p5,
		$p6
	) {
	}

	/**
	 * @param scalar   $p1
	 * @param null     $p2
	 * @param iterable $p3
	 * @param callable $p4
	 * @param resource $p5
	 * @param mixed    $p6
	 * @param object   $p7
	 *
	 * @return no-return
	 */
	public function f5(
		$p1,
		$p2,
		$p3,
		$p4,
		$p5,
		$p6,
		$p7
	) {
	}

	/**
	 * @return noreturn
	 */
	public function f6()
	{
	}

	/**
	 * @return static
	 */
	public function f7()
	{
	}

	/**
	 * @return self
	 */
	public function f8()
	{
	}

	/**
	 * @return $this
	 */
	public function f9()
	{
	}
}
