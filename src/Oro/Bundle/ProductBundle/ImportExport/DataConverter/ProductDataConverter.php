<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
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
    protected ?TokenAccessorInterface $tokenAccessor = null;
    /**
     * @var ContextInterface
     */
    protected $context;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor): void
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    protected function isFieldAvailableForExport(string $entityName, string $fieldName): bool
    {
        $isAvailable = parent::isFieldAvailableForExport($entityName, $fieldName);
        $attrConfig = $this->fieldHelper->getFieldConfig('attribute', $entityName, $fieldName);
        if ($isAvailable
            && null !== $attrConfig
            && $attrConfig->is('is_attribute')
            && !$this->isAvailableAttribute($attrConfig)
        ) {
            $isAvailable = false;
            $this->availableForExportField[$entityName][$fieldName] = false;
        }

        return $isAvailable;
    }

    private function isAvailableAttribute(ConfigInterface $attrConfig): bool
    {
        $organizationId = $this->tokenAccessor->getOrganizationId();

        return $attrConfig->is('is_global')
            || ($organizationId && $attrConfig->get('organization_id') === $organizationId);
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
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
