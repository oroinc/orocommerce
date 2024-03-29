<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Preloads line items to-one and to-many relations to avoid one-by-one separate queries.
 */
class PreloadCheckoutOnStartFromShoppingListEventListener
{
    private PreloadingManager $preloadingManager;

    private array $fieldsToPreload = [
        'product' => [
            'backOrder' => [],
            'category' => [
                'backOrder' => [],
                'decrementQuantity' => [],
                'highlightLowInventory' => [],
                'inventoryThreshold' => [],
                'isUpcoming' => [],
                'lowInventoryThreshold' => [],
                'manageInventory' => [],
                'maximumQuantityToOrder' => [],
                'minimumQuantityToOrder' => [],
            ],
            'decrementQuantity' => [],
            'highlightLowInventory' => [],
            'inventoryThreshold' => [],
            'isUpcoming' => [],
            'lowInventoryThreshold' => [],
            'manageInventory' => [],
            'maximumQuantityToOrder' => [],
            'minimumQuantityToOrder' => [],
            'unitPrecisions' => [],
        ],
        'kitItemLineItems' => [
            'kitItem' => [
                'labels' => [],
                'productUnit' => [],
            ],
            'product' => [
                'names' => [],
                'images' => [
                    'image' => [
                        'digitalAsset' => [
                            'titles' => [],
                            'sourceFile' => [
                                'digitalAsset' => [],
                            ],
                        ],
                    ],
                    'types' => [],
                ],
                'unitPrecisions' => [],
            ],
            'unit' => [],
        ],
    ];

    public function __construct(PreloadingManager $preloadingManager)
    {
        $this->preloadingManager = $preloadingManager;
    }

    public function setFieldsToPreload(array $fieldsToPreload): void
    {
        $this->fieldsToPreload = $fieldsToPreload;
    }

    public function onStartFromShoppingList(ExtendableConditionEvent $event): void
    {
        $context = $event->getContext();
        if (!$context instanceof WorkflowItem) {
            return;
        }

        $shoppingList = $context->getResult()->get('shoppingList');
        if (!$shoppingList instanceof ShoppingList) {
            return;
        }

        $this->preloadingManager->preloadInEntities($shoppingList->getLineItems()->toArray(), $this->fieldsToPreload);
    }
}
