<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;

class RFPOperationListener
{
    /**
     * @var ProductFormProvider
     */
    protected $productFormProvider;

    /**
     * @var QuickAddCollectionProvider
     */
    protected $collectionProvider;

    public function __construct(QuickAddCollectionProvider $collectionProvider)
    {
        $this->collectionProvider = $collectionProvider;
    }

    public function onCopyPasteRFPFormSubmitCheck(FormSubmitCheckEvent $event)
    {
        $collection = $this->collectionProvider->processCopyPaste();

        if (!$collection) {
            return;
        }

        $event->setShouldSubmitOnError($this->shouldSubmitOnError($collection));
    }

    public function onQuickAddImportRFPFormSubmitCheck(FormSubmitCheckEvent $event)
    {
        $collection = $this->collectionProvider->processImport();
        if (!$collection) {
            return;
        }

        $event->setShouldSubmitOnError($this->shouldSubmitOnError($collection));
    }

    /**
     * @param QuickAddRowCollection|null $collection
     * @return bool
     */
    protected function shouldSubmitOnError(QuickAddRowCollection $collection)
    {
        /** @var QuickAddRow $row */
        foreach ($collection->getInvalidRows() as $row) {
            if (!$this->isRFPAllowedErrors($row->getErrors())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $errors
     * @return bool
     */
    protected function isRFPAllowedErrors($errors)
    {
        foreach ($errors as $errorMessage) {
            if (!array_key_exists('parameters', $errorMessage)
                || !array_key_exists('allowedRFP', $errorMessage['parameters'])
                || false === $errorMessage['parameters']['allowedRFP']
            ) {
                return false;
            }
        }

        return true;
    }
}
