<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class QuoteUpdateHandlerEventListener
{
    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @var QuoteRequestHandler
     */
    private $quoteRequestHandler;

    /**
     * @param WebsiteManager $websiteManager
     * @param QuoteRequestHandler $quoteRequestHandler
     */
    public function __construct(WebsiteManager $websiteManager, QuoteRequestHandler $quoteRequestHandler)
    {
        $this->websiteManager = $websiteManager;
        $this->quoteRequestHandler = $quoteRequestHandler;
    }

    /**
     * @param FormProcessEvent $event
     */
    public function beforeDataSet(FormProcessEvent $event)
    {
        /** @var Quote $quote */
        $quote = $event->getData();

        if (!$quote->getWebsite()) {
            $quote->setWebsite($this->websiteManager->getDefaultWebsite());
        }
    }

    /**
     * @param FormProcessEvent $event
     */
    public function beforeSubmit(FormProcessEvent $event)
    {
        /** @var Quote $quote */
        $quote = $event->getData();

        $quote->setCustomer($this->quoteRequestHandler->getCustomer());
        $quote->setCustomerUser($this->quoteRequestHandler->getCustomerUser());
    }
}
