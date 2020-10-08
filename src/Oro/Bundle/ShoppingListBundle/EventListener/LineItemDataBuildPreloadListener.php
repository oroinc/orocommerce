<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;

/**
 * Preloads line items in LineItemDataBuildEvent.
 */
class LineItemDataBuildPreloadListener
{
    /** @var PreloadingManager */
    private $preloadingManager;

    /** @var array */
    private $fieldsToPreload = [
        'parentProduct' => [
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
        ],
        'product' => [
            'isUpcoming' => [],
            'highlightLowInventory' => [],
            'minimumQuantityToOrder' => [],
            'maximumQuantityToOrder' => [],
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
            'category' => [],
        ],
    ];

    /**
     * @param PreloadingManager $preloadingManager
     */
    public function __construct(PreloadingManager $preloadingManager)
    {
        $this->preloadingManager = $preloadingManager;
    }

    /**
     * @param array $fieldsToPreload
     */
    public function setFieldsToPreload(array $fieldsToPreload): void
    {
        $this->fieldsToPreload = $fieldsToPreload;
    }

    /**
     * @param LineItemDataBuildEvent $event
     */
    public function onLineItemData(LineItemDataBuildEvent $event): void
    {
        $this->preloadingManager->preloadInEntities($event->getLineItems(), $this->fieldsToPreload);
    }
}
