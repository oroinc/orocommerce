<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Resolve prices on ImportExportResult post persist.
 */
final class ImportExportResultListener
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $producer
    ) {
    }

    public function postPersist(ImportExportResult $importExportResult)
    {
        if (!$this->isSupported($importExportResult)) {
            return;
        }

        $type = $importExportResult->getType();
        $options = $importExportResult->getOptions();
        $priceListId = $options['price_list_id'];
        $version = $options['importVersion'];
        $priceList = $this->doctrine
            ->getManagerForClass(PriceList::class)
            ?->find(PriceList::class, $priceListId);

        if ($priceList !== null && $type !== ProcessorRegistry::TYPE_IMPORT_VALIDATION) {
            $this->producer->send(
                GenerateDependentPriceListPricesTopic::getName(),
                [
                    'sourcePriceListId' => $priceList->getId(),
                    'version' => $version
                ]
            );
        }
    }

    private function isSupported(ImportExportResult $importExportResult): bool
    {
        $options = $importExportResult->getOptions();

        return isset($options['price_list_id']) && isset($options['importVersion']);
    }
}
