<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
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
    private $websiteManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param WebsiteManager $websiteManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(WebsiteManager $websiteManager, DoctrineHelper $doctrineHelper)
    {
        $this->websiteManager = $websiteManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $order = $context->getResult();

        if (!$order instanceof Order) {
            return;
        }

        if (null !== $order->getWebsite()) {
            return;
        }

        $requestData = $context->getRequestData();
        if (array_key_exists('website', $requestData)) {
            return;
        }

        $website = $this->websiteManager->getDefaultWebsite();

        if (null === $website) {
            return;
        }

        $order->setWebsite($website);

        $context->setResult($order);
    }
}
