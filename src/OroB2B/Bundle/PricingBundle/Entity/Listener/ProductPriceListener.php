<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Listener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Rounding\RoundingService;

class ProductPriceListener
{
    /**
     * @var RoundingService
     */
    protected $roundingService;

    /**
     * @param RoundingService $roundingService
     */
    public function __construct(RoundingService $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function prePersist(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->roundPrice($productPrice->getPrice());
    }

    /**
     * @param ProductPrice $productPrice
     * @param LifecycleEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $this->roundPrice($productPrice->getPrice());
    }

    /**
     * @param Price|null $price
     */
    protected function roundPrice(Price $price = null)
    {
        if (!$price) {
            return;
        }

        $price->setValue(
            $this->roundingService->round($price->getValue())
        );
    }
}
