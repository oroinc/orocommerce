<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

/**
 * This listener ensures that price manager's flush occurs before entity manager's flush during product's saving.
 */
class ProductFormListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var PriceManager
     */
    private $priceManager;

    public function __construct(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    public function onBeforeFlush(AfterFormProcessEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if ($event->getData()->getId()) {
            $this->priceManager->flush();
        }
    }
}
