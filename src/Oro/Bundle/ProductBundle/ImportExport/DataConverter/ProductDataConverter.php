<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Prepares Product data for import and export
 */
class ProductDataConverter extends LocalizedFallbackValueAwareDataConverter implements ContextAwareInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ContextInterface
     */
    protected $context;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $backendHeader = parent::getBackendHeader();

        // According to business logic Product should have Primary and Additional unit precisions.
        // But Product Entity has primaryUnitPrecision property and unitPrecisions property which
        // is collection of all unit precisions. AdditionalUnitPrecisions is calculated by excluding
        // PrimaryUnitPrecision from all UnitPrecisions. This fix was done in order to display
        // correct column headers during Products Export.
        foreach ($backendHeader as $k => &$v) {
            $arr = explode(':', $v);
            if ($arr[0] === 'unitPrecisions') {
                if ((int)$arr[1] === 0) {
                    unset($backendHeader[$k]);
                } else {
                    $arr[0] = 'additionalUnitPrecisions';
                    --$arr[1];
                    $v = implode(':', $arr);
                }
            }
        }
        unset($v);

        if ($this->eventDispatcher) {
            $event = new ProductDataConverterEvent($backendHeader);
            $event->setContext($this->context);

            $this->eventDispatcher->dispatch($event, ProductDataConverterEvent::BACKEND_HEADER);
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
            $event->setContext($this->context);

            $this->eventDispatcher->dispatch($event, ProductDataConverterEvent::CONVERT_TO_EXPORT);
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
            $event->setContext($this->context);

            $this->eventDispatcher->dispatch($event, ProductDataConverterEvent::CONVERT_TO_IMPORT);
            $data = $event->getData();
        }

        return $data;
    }

    public function setRelationCalculator(RelationCalculatorInterface $relationCalculator)
    {
        $this->relationCalculator = $relationCalculator;
    }
}
