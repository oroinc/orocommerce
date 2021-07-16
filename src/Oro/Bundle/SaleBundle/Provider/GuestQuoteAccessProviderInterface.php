<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Must be implemented by providers through which can be determined that quote is accessible by quest link.
 */
interface GuestQuoteAccessProviderInterface
{
    public function isGranted(Quote $quote): bool;
}
