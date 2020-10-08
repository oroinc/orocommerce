<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Oro\Bundle\ShoppingListBundle\EventListener\LineItemDataBuildPreloadListener;

class LineItemDataBuildPreloadListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreloadingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $preloadingManger;

    /** @var LineItemDataBuildPreloadListener */
    private $listener;

    protected function setUp(): void
    {
        $this->preloadingManger = $this->createMock(PreloadingManager::class);
        $this->listener = new LineItemDataBuildPreloadListener($this->preloadingManger);
    }

    public function testOnLineItemData(): void
    {
        $lineItems = [new LineItem()];

        $this->preloadingManger
            ->expects($this->once())
            ->method('preloadInEntities')
            ->with(
                $lineItems,
                [
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
                ]
            );

        $event = new LineItemDataBuildEvent($lineItems, ['sample_context']);
        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenFieldsOverridden(): void
    {
        $lineItems = [new LineItem()];

        $this->preloadingManger
            ->expects($this->once())
            ->method('preloadInEntities')
            ->with($lineItems, ['customField']);

        $event = new LineItemDataBuildEvent($lineItems, ['sample_context']);
        $this->listener->setFieldsToPreload(['customField']);
        $this->listener->onLineItemData($event);
    }
}
