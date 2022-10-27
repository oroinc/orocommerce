<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

/**
 * If respective feature is enabled and conditions acceptable
 * - calls frontend product list modifier on query given in event
 */
class ProductSelectPriceListAwareListener
{
    public const DEFAULT_ACCOUNT_USER = 'default_customer_user';

    private FrontendProductListModifier $modifier;
    private ManagerRegistry $doctrine;

    public function __construct(FrontendProductListModifier $modifier, ManagerRegistry $doctrine)
    {
        $this->modifier = $modifier;
        $this->doctrine = $doctrine;
    }

    public function onDBQuery(ProductDBQueryRestrictionEvent $event): void
    {
        if (!$event->getDataParameters()->has('price_list')) {
            return;
        }

        $priceListParam = $event->getDataParameters()->get('price_list');
        $priceList = self::DEFAULT_ACCOUNT_USER !== $priceListParam
            ? $this->getPriceListById($priceListParam)
            : null;

        $this->modifier->applyPriceListLimitations($event->getQueryBuilder(), null, $priceList);
    }

    private function getPriceListById(int $priceListId): ?PriceList
    {
        return $this->doctrine->getRepository(PriceList::class)->find($priceListId);
    }
}
