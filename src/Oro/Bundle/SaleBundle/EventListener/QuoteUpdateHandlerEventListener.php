<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Handler\QuoteCustomerDataRequestHandler;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Listens form process event to populate Quote entity with data from Request
 */
class QuoteUpdateHandlerEventListener
{
    public function __construct(
        private readonly WebsiteManager $websiteManager,
        private readonly QuoteCustomerDataRequestHandler $quoteCustomerDataRequestHandler,
    ) {
    }

    public function ensureWebsite(FormProcessEvent $event)
    {
        /** @var Quote $quote */
        $quote = $event->getData();

        if (!$quote->getWebsite()) {
            $quote->setWebsite($this->websiteManager->getDefaultWebsite());
        }
    }

    public function ensureCustomer(FormProcessEvent $event)
    {
        $quote = $event->getData();

        $this->quoteCustomerDataRequestHandler->handle($quote);
    }
}
