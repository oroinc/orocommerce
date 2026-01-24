<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag;

use Oro\Bundle\SaleBundle\Quote\Calculable\CalculableQuoteInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Calculable quote implementation using ParameterBag for flexible data storage.
 *
 * Extends ParameterBag to provide a flexible, parameter-based approach to storing and accessing quote data
 * for calculation purposes, including line items and other quote-related information.
 */
class ParameterBagCalculableQuote extends ParameterBag implements CalculableQuoteInterface
{
    const FIELD_LINE_ITEMS = 'line_items';

    #[\Override]
    public function getLineItems()
    {
        return $this->get(self::FIELD_LINE_ITEMS);
    }
}
