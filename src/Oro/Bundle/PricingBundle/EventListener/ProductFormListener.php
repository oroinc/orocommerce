<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

/**
 * This listener ensures that price manager's flush occurs before entity manager's flush during product's saving.
 */
class ProductFormListener
{
    /**
     * @var PriceManager
     */
    private $priceManager;

    /**
     * @param PriceManager $priceManager
     */
    public function __construct(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function onBeforeFlush(AfterFormProcessEvent $event)
    {
        if ($event->getData()->getId()) {
            $this->priceManager->flush();
        }
    }
}
