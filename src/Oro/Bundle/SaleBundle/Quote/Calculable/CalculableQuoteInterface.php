<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

/**
 * Defines the contract for quotes that support calculation of totals and subtotals.
 *
 * Extends {@see LineItemsAwareInterface} to provide access to line items, enabling the calculation of quote totals,
 * subtotals, and other financial metrics.
 */
interface CalculableQuoteInterface extends LineItemsAwareInterface
{
}
