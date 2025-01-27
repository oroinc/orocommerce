<?php

namespace Oro\Bundle\SaleBundle\Form\Handler;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles Quote on POST/PUT request to replace saved Customer and Customer User with new from request
 */
class QuoteCustomerDataRequestHandler
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly QuoteRequestHandler $quoteRequestHandler
    ) {
    }

    public function handle(Quote $quote): void
    {
        if (!in_array($this->requestStack->getCurrentRequest()->getMethod(), ['POST', 'PUT'], true)) {
            return;
        }

        $quote->setCustomer($this->quoteRequestHandler->getCustomer());
        $quote->setCustomerUser($this->quoteRequestHandler->getCustomerUser());
    }
}
