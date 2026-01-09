<?php

namespace Oro\Bundle\PromotionBundle\Discount\Exception;

/**
 * Thrown when a discount object does not implement
 * the required {@see \Oro\Bundle\PromotionBundle\Discount\DiscountInterface}.
 */
class UnsupportedDiscountException extends \InvalidArgumentException
{
}
