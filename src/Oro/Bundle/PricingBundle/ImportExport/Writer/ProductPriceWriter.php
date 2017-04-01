<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductPriceWriter extends PersistentBatchWriter
{
    /**
     * @var PriceManager
     */
    protected $priceManager;

    public function __construct(
        RegistryInterface $registry,
        EventDispatcherInterface $eventDispatcher,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger,
        PriceManager $priceManager
    ) {
        $this->priceManager = $priceManager;
        parent::__construct($registry, $eventDispatcher, $contextRegistry, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $this->clearContext();

        parent::write($items);
    }

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
}
