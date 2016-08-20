<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

/**
 * Remove product prices by unit on ProductUnitPrecision delete.
 */
class ProductSelectPriceListAwareListener
{
    const DEFAULT_ACCOUNT_USER = 'default_account_user';

    /**
     * @var ProductSelectDBQueryEvent
     */
    protected $event;

    /**
     * @var FrontendProductListModifier
     */
    protected $modifier;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param FrontendProductListModifier $modifier
     * @param Registry $registry
     */
    public function __construct(FrontendProductListModifier $modifier, Registry $registry)
    {
        $this->modifier = $modifier;
        $this->registry = $registry;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        $this->event = $event;

        if (!$this->isConditionsAcceptable()) {
            return;
        }

        $priceList = $this->getPriceListParam() !== self::DEFAULT_ACCOUNT_USER
            ? $this->getPriceListById($this->getPriceListParam())
            : null;
        
        $this->modifier->applyPriceListLimitations($this->event->getQueryBuilder(), null, $priceList);
    }

    /**
     * @return bool
     */
    protected function isConditionsAcceptable()
    {
        return $this->event->getDataParameters()->has('price_list');
    }

    /**
     * @return int|string
     */
    protected function getPriceListParam()
    {
        return $this->event->getDataParameters()->get('price_list');
    }

    /**
     * @param int $priceListId
     * @return \Oro\Bundle\PricingBundle\Entity\PriceList
     */
    protected function getPriceListById($priceListId)
    {
        return $this->registry->getManagerForClass('OroPricingBundle:PriceList')
            ->getRepository('OroPricingBundle:PriceList')
            ->find($priceListId);
    }
}
