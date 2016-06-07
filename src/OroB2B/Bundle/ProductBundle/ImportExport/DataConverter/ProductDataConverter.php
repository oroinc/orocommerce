<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\DataConverter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverter extends LocalizedFallbackValueAwareDataConverter
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $backendHeader = parent::getBackendHeader();

        if ($this->eventDispatcher) {
            $event = new ProductDataConverterEvent($backendHeader);
            $this->eventDispatcher->dispatch(ProductDataConverterEvent::BACKEND_HEADER, $event);
            $backendHeader = $event->getData();
        }

        return $backendHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        $data = parent::convertToExportFormat($exportedRecord, $skipNullValues);

        if ($this->eventDispatcher) {
            $event = new ProductDataConverterEvent($data);
            $this->eventDispatcher->dispatch(ProductDataConverterEvent::CONVERT_TO_EXPORT, $event);
            $data = $event->getData();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $data = parent::convertToImportFormat($importedRecord, $skipNullValues);

        if ($this->eventDispatcher) {
            $event = new ProductDataConverterEvent($data);
            $this->eventDispatcher->dispatch(ProductDataConverterEvent::CONVERT_TO_IMPORT, $event);
            $data = $event->getData();
        }

        return $data;
    }

    /**
     * @param RelationCalculatorInterface $relationCalculator
     */
    public function setRelationCalculator(RelationCalculatorInterface $relationCalculator)
    {
        $this->relationCalculator = $relationCalculator;
    }
}
