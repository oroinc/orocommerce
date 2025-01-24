<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * Sets `quoteProductOffers` to each record of the datagrid showing {@see QuoteProduct} records.
 */
class DatagridProductOffersLoaderListener
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $records = $event->getRecords();

        $quoteProductIds = [];
        foreach ($records as $record) {
            $quoteProductIds[] = $record->getValue('id');
        }

        $productOfferCollections = $this->doctrine->getRepository(QuoteProductOffer::class)
            ->getProductOffersByQuoteIds($quoteProductIds);

        foreach ($records as $record) {
            $record->setValue(
                'quoteProductOffers',
                $productOfferCollections[$record->getValue('id')] ?? new ArrayCollection()
            );
        }
    }
}
