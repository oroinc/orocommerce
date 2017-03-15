<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

class WebsiteOrderApiProcessor implements ProcessorInterface
{
    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        if (false === $this->isWebsiteProcessorApplicable($context)) {
            return;
        }

        $website = $this->websiteManager->getDefaultWebsite();
        /** @var Order $order */
        $order = $context->getResult();

        $order->setWebsite($website);
        $context->setResult($order);
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function isWebsiteProcessorApplicable(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return false;
        }

        $order = $context->getResult();

        if (!$order instanceof Order) {
            return false;
        }

        if (null !== $order->getWebsite()) {
            return false;
        }

        $requestData = $context->getRequestData();
        if (array_key_exists('website', $requestData)) {
            return false;
        }

        $website = $this->websiteManager->getDefaultWebsite();

        if (null === $website) {
            return false;
        }

        return true;
    }
}
