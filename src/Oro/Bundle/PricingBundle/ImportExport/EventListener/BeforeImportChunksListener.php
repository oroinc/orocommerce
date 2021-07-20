<?php

namespace Oro\Bundle\PricingBundle\ImportExport\EventListener;

use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Clears the price list before the import for the reset import strategy
 */
class BeforeImportChunksListener
{
    const RESET_PROCESSOR_ALIAS = 'oro_pricing_product_price.reset';

    /** @var ManagerRegistry */
    private $registry;

    /** @var ShardManager */
    private $shardManager;

    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
    }

    public function onBeforeImportChunks(BeforeImportChunksEvent $event)
    {
        $body = $event->getBody();

        if ($this->isResetStrategyApplicable($body)) {
            if (!isset($body['options']['price_list_id'])) {
                return;
            }

            $priceListId = (int)$body['options']['price_list_id'];
            /** @var PriceList $priceList */
            $priceList = $this->getPriceListById($priceListId);
            if ($priceList) {
                $this->registry->getRepository(ProductPrice::class)
                    ->deleteByPriceList($this->shardManager, $priceList);
            } else {
                return;
            }
        }
    }

    /**
     * @param array $body
     * @return bool
     */
    protected function isResetStrategyApplicable(array $body)
    {
        if (isset($body['processorAlias']) && $body['processorAlias'] === self::RESET_PROCESSOR_ALIAS) {
            return true;
        }

        return false;
    }

    /**
     * @param int $priceListId
     * @return null|PriceList
     */
    protected function getPriceListById(int $priceListId)
    {
        return $this->registry->getRepository(PriceList::class)->find($priceListId);
    }
}
