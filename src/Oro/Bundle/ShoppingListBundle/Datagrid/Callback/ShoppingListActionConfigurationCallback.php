<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Callback;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Determines which of the actions in the grid row will be available
 */
class ShoppingListActionConfigurationCallback
{
    /** @var array */
    private $cachedIsGranted = [];

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductMatrixAvailabilityProvider */
    private $productMatrixAvailabilityProvider;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function checkActions(ResultRecordInterface $record): array
    {
        $actionsNotAvailable = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'delete' => false,
        ];
        $shoppingListId = $record->getValue('shoppingListId');
        if (!$shoppingListId) {
            return $actionsNotAvailable;
        }

        if (!isset($this->cachedIsGranted[$shoppingListId])) {
            $shoppingListEntityReference = $this->doctrineHelper->getEntityReference(
                ShoppingList::class,
                $shoppingListId
            );

            $this->cachedIsGranted[$shoppingListId] = $this->authorizationChecker->isGranted(
                'oro_shopping_list_frontend_update',
                $shoppingListEntityReference
            );
        }

        if ($this->cachedIsGranted[$shoppingListId] === false) {
            return $actionsNotAvailable;
        }

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
