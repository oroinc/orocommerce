<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Preloads line items to-one and to-many relations to avoid one-by-one separate queries.
 */
class PreloadCheckoutOnStartEventListener
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
            'productUnit' => [],
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

    public function onStart(ExtendableConditionEvent $event): void
    {
        $context = $event->getContext();
        if (!$context instanceof ActionData) {
            return;
        }

        $checkout = $context->get('checkout');
        if (!$checkout instanceof Checkout) {
            return;
        }

        $this->preloadingManager->preloadInEntities($checkout->getLineItems()->toArray(), $this->fieldsToPreload);
    }
}
