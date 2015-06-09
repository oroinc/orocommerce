<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use OroB2B\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;

class ProductPriceWriter extends PersistentBatchWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $this->clearContext();

        parent::write($items);
    }

    protected function clearContext()
    {
        $this->contextRegistry
            ->getByStepExecution($this->stepExecution)
            ->setValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH, null);
    }
}
