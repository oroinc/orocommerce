<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

class ProductPriceWriter extends PersistentBatchWriter
{
    /**
     * @var PriceManager
     */
    protected $priceManager;

    protected function saveItems(array $items, EntityManager $em)
    {
        foreach ($items as $item) {
            $this->priceManager->persist($item);
        }
        $this->priceManager->flush();
        $em->flush();
    }

    protected function clearContext()
    {
        $this->contextRegistry
            ->getByStepExecution($this->stepExecution)
            ->setValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH, null);
    }

    /**
     * @param PriceManager $priceManager
     */
    public function setPriceManager(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }
}
