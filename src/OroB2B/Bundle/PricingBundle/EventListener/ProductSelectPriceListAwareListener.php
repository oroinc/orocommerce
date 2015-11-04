<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

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

    public function __construct(FrontendProductListModifier $modifier)
    {
        $this->modifier = $modifier;
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

        $this->modifier->applyPriceListLimitations($this->event->getQueryBuilder());
    }

    /**
     * @return bool
     */
    protected function isConditionsAcceptable()
    {
        return $this->event->getDataParameters()->get('price_list', null) === self::DEFAULT_ACCOUNT_USER;
    }
}
