<?php

namespace GoodPhp\Reflection\Type\Template;

enum TemplateTypeVariance
{
	/*
	 * Allow to be both produced and consumed.
	 */
	case INVARIANT;
	/*
	 * Only allowed to be produced (outbound returns, no inbound parameters).
	 */
	case COVARIANT;
	/*
	 * Only allowed to be consumed (inbound parameters, no outbound returns).
	 */
	case CONTRAVARIANT;
}
