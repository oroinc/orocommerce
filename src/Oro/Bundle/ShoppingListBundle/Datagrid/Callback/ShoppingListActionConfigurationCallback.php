<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Callback;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;

/**
 * Determines which of the actions in the grid row will be available
 */
class ShoppingListActionConfigurationCallback
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductMatrixAvailabilityProvider */
    private $productMatrixAvailabilityProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function checkActions(ResultRecordInterface $record): array
    {
        $isConfigurable = $record->getValue('isConfigurable');
        if (!$isConfigurable) {
            return ['update_configurable' => false, 'add_notes' => !$record->getValue('notes'), 'edit_notes' => false];
        }

        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        /** @var Product $product */
        $product = $productRepository->find($record->getValue('productId'));
        $isAvailable = $product && $this->productMatrixAvailabilityProvider->isMatrixFormAvailable($product);

        return ['update_configurable' => $isAvailable, 'add_notes' => false, 'edit_notes' => false];
    }
}
