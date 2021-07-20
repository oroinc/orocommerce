<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

/**
 * If respective feature is enabled and conditions acceptable
 * - calls frontend product list modifier on query given in event
 */
class ProductSelectPriceListAwareListener
{
    const DEFAULT_ACCOUNT_USER = 'default_customer_user';

    /**
     * @var ProductDBQueryRestrictionEvent
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

    public function __construct(FrontendProductListModifier $modifier, Registry $registry)
    {
        $this->modifier = $modifier;
        $this->registry = $registry;
    }

    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
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
     * @return PriceList
     */
    protected function getPriceListById($priceListId)
    {
        return $this->registry->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class)
            ->find($priceListId);
    }
}
