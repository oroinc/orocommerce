<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;

/**
 * Describes an instance of {@see ProductKitItemLineItemInterface} that is aware of {@see Price}.
 */
interface ProductKitItemLineItemPriceAwareInterface extends ProductKitItemLineItemInterface, PriceAwareInterface
{
}
