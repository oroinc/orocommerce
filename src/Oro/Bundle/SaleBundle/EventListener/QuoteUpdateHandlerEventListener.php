<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param WebsiteManager $websiteManager
     * @param QuoteRequestHandler $quoteRequestHandler
     * @param RequestStack $requestStack
     */
    public function __construct(
        WebsiteManager $websiteManager,
        QuoteRequestHandler $quoteRequestHandler,
        RequestStack $requestStack
    ) {
        $this->websiteManager = $websiteManager;
        $this->quoteRequestHandler = $quoteRequestHandler;
        $this->requestStack = $requestStack;
    }

    /**
     * @param FormProcessEvent $event
     */
    public function ensureWebsite(FormProcessEvent $event)
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
    public function ensureCustomer(FormProcessEvent $event)
    {
        $quote = $event->getData();

        if (in_array($this->requestStack->getCurrentRequest()->getMethod(), ['POST', 'PUT'], true)) {
            $quote->setCustomer($this->quoteRequestHandler->getCustomer());
            $quote->setCustomerUser($this->quoteRequestHandler->getCustomerUser());
        }
    }
}
