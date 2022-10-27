<?php

namespace Oro\Bundle\PricingBundle\ImportExport\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Clears the price list before the import for the reset import strategy
 */
class BeforeImportChunksListener
{
    public const RESET_PROCESSOR_ALIAS = 'oro_pricing_product_price.reset';

    private ManagerRegistry $doctrine;
    private ShardManager $shardManager;

    public function __construct(ManagerRegistry $doctrine, ShardManager $shardManager)
    {
        $this->doctrine = $doctrine;
        $this->shardManager = $shardManager;
    }

    public function onBeforeImportChunks(BeforeImportChunksEvent $event): void
    {
        $body = $event->getBody();
        if ($this->isResetStrategyApplicable($body)) {
            if (!isset($body['options']['price_list_id'])) {
                return;
            }

            $priceListId = (int)$body['options']['price_list_id'];
            $priceList = $this->getPriceListById($priceListId);
            if (null !== $priceList) {
                $this->doctrine->getRepository(ProductPrice::class)
                    ->deleteByPriceList($this->shardManager, $priceList);
            }
        }
    }

    private function isResetStrategyApplicable(array $body): bool
    {
        return isset($body['processorAlias']) && $body['processorAlias'] === self::RESET_PROCESSOR_ALIAS;
    }

    private function getPriceListById(int $priceListId): ?PriceList
    {
        return $this->doctrine->getRepository(PriceList::class)->find($priceListId);
    }
}
