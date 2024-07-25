<?php

namespace Oro\Bundle\SaleBundle\Workflow\ActionGroup;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Service to accept Quote and start Checkout workflow for Order submission.
 */
interface AcceptQuoteAndSubmitToOrderInterface
{
    public function execute(QuoteDemand $data): array;
}
