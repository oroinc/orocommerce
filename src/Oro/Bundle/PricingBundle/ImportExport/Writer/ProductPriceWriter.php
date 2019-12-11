<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductPriceWriter extends PersistentBatchWriter
{
    /**
     * @var OptionalListenerManager
     */
    protected $listenerManager;

    /**
     * @var PriceManager
     */
    protected $priceManager;

    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @param ManagerRegistry          $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ContextRegistry          $contextRegistry
     * @param LoggerInterface          $logger
     * @param PriceManager             $priceManager
     * @param OptionalListenerManager  $listenerManager
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger,
        PriceManager $priceManager,
        OptionalListenerManager $listenerManager
    ) {
        $this->priceManager = $priceManager;
        $this->listenerManager = $listenerManager;
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

    /**
     * @param array         $items
     * @param EntityManager $em
     */
    protected function saveItems(array $items, EntityManager $em)
    {
        $this->listenerManager->disableListeners($this->listeners);
        foreach ($items as $item) {
            $this->priceManager->persist($item);
        }
        $this->priceManager->flush();
        $em->flush();
        $this->listenerManager->enableListeners($this->listeners);
    }

    protected function clearContext()
    {
        $this->contextRegistry
            ->getByStepExecution($this->stepExecution)
            ->setValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH, null);
    }

    /**
     * @param string $listener
     */
    public function disableListener($listener)
    {
        $this->listeners[] = $listener;
    }
}
