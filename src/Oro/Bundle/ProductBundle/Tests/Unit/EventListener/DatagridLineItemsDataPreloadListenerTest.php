<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridLineItemsDataPreloadListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataPreloadListenerTest extends TestCase
{
    private PreloadingManager|MockObject $preloadingManager;

    private DatagridLineItemsDataPreloadListener $listener;

    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);
        $this->listener = new DatagridLineItemsDataPreloadListener($this->preloadingManager);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnLineItemDataWhenGrouped(): void
    {
        $lineItemSimple = $this->createMock(ProductLineItemInterface::class);
        $lineItemSimple
            ->method('getEntityIdentifier')
            ->willReturn(10);

        $lineItemConfigurable = $this->createMock(ProductLineItemInterface::class);
        $lineItemConfigurable
            ->method('getEntityIdentifier')
            ->willReturn(20);
        $lineItemConfigurable
            ->method('getParentProduct')
            ->willReturn($this->createMock(Product::class));

        $lineItemKit = new ProductKitItemLineItemsAwareStub(30);

        $this->preloadingManager
            ->expects(self::exactly(3))
            ->method('preloadInEntities')
            ->withConsecutive(
                [
                    [$lineItemSimple],
                    [
                        'product' => [
                            'names' => [],
                            'isUpcoming' => [],
                            'highlightLowInventory' => [],
                            'minimumQuantityToOrder' => [],
                            'maximumQuantityToOrder' => [],
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
                            'category' => [
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
                            ],
                        ],
                    ],
                ],
                [
                    [$lineItemConfigurable],
                    [
                        'parentProduct' => [
                            'names' => [],
                            'unitPrecisions' => [],
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
                            'names' => [],
                            'isUpcoming' => [],
                            'highlightLowInventory' => [],
                            'minimumQuantityToOrder' => [],
                            'maximumQuantityToOrder' => [],
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
                            'category' => [
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
                            ],
                        ],
                    ],
                ],
                [
                    [$lineItemKit],
                    [
                        'product' => [
                            'names' => [],
                            'isUpcoming' => [],
                            'highlightLowInventory' => [],
                            'minimumQuantityToOrder' => [],
                            'maximumQuantityToOrder' => [],
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
                            'category' => [
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
                            ],
                        ],
                        'kitItemLineItems' => [
                            'kitItem' => [
                                'labels' => [],
                            ],
                            'product' => [
                                'names' => [],
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
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
                                'category' => [
                                    'isUpcoming' => [],
                                    'highlightLowInventory' => [],
                                    'minimumQuantityToOrder' => [],
                                    'maximumQuantityToOrder' => [],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $event = new DatagridLineItemsDataEvent(
            [
                $lineItemSimple->getEntityIdentifier() => $lineItemSimple,
                $lineItemConfigurable->getEntityIdentifier() => $lineItemConfigurable,
                $lineItemKit->getEntityIdentifier() => $lineItemKit,
            ],
            [
                $lineItemSimple->getEntityIdentifier() => ['type' => Product::TYPE_SIMPLE],
                $lineItemConfigurable->getEntityIdentifier() => ['type' => Product::TYPE_CONFIGURABLE],
                $lineItemKit->getEntityIdentifier() => ['type' => Product::TYPE_KIT],
            ],
            $this->createMock(Datagrid::class),
            ['isGrouped' => true]
        );
        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider onLineItemDataWhenUngroupedDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnLineItemDataWhenUngrouped(array $context): void
    {
        $lineItemSimple = $this->createMock(ProductLineItemInterface::class);
        $lineItemSimple
            ->method('getEntityIdentifier')
            ->willReturn(10);

        $lineItemConfigurable = $this->createMock(ProductLineItemInterface::class);
        $lineItemConfigurable
            ->method('getEntityIdentifier')
            ->willReturn(20);
        $lineItemConfigurable
            ->method('getParentProduct')
            ->willReturn($this->createMock(Product::class));

        $lineItemKit = new ProductKitItemLineItemsAwareStub(30);

        $this->preloadingManager
            ->expects(self::exactly(3))
            ->method('preloadInEntities')
            ->withConsecutive(
                [
                    [$lineItemSimple],
                    [
                        'product' => [
                            'names' => [],
                            'isUpcoming' => [],
                            'highlightLowInventory' => [],
                            'minimumQuantityToOrder' => [],
                            'maximumQuantityToOrder' => [],
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
                            'category' => [
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
                            ],
                        ],
                    ],
                ],
                [
                    [$lineItemConfigurable],
                    [
                        'parentProduct' => [
                            'names' => [],
                        ],
                        'product' => [
                            'names' => [],
                            'isUpcoming' => [],
                            'highlightLowInventory' => [],
                            'minimumQuantityToOrder' => [],
                            'maximumQuantityToOrder' => [],
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
                            'category' => [
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
                            ],
                        ],
                    ],
                ],
                [
                    [$lineItemKit],
                    [
                        'product' => [
                            'names' => [],
                            'isUpcoming' => [],
                            'highlightLowInventory' => [],
                            'minimumQuantityToOrder' => [],
                            'maximumQuantityToOrder' => [],
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
                            'category' => [
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
                            ],
                        ],
                        'kitItemLineItems' => [
                            'kitItem' => [
                                'labels' => [],
                            ],
                            'product' => [
                                'names' => [],
                                'isUpcoming' => [],
                                'highlightLowInventory' => [],
                                'minimumQuantityToOrder' => [],
                                'maximumQuantityToOrder' => [],
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
                                'category' => [
                                    'isUpcoming' => [],
                                    'highlightLowInventory' => [],
                                    'minimumQuantityToOrder' => [],
                                    'maximumQuantityToOrder' => [],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $event = new DatagridLineItemsDataEvent(
            [
                $lineItemSimple->getEntityIdentifier() => $lineItemSimple,
                $lineItemConfigurable->getEntityIdentifier() => $lineItemConfigurable,
                $lineItemKit->getEntityIdentifier() => $lineItemKit,
            ],
            [
                $lineItemSimple->getEntityIdentifier() => ['type' => Product::TYPE_SIMPLE],
                $lineItemConfigurable->getEntityIdentifier() => ['type' => Product::TYPE_CONFIGURABLE],
                $lineItemKit->getEntityIdentifier() => ['type' => Product::TYPE_KIT],
            ],
            $this->createMock(Datagrid::class),
            $context
        );
        $this->listener->onLineItemData($event);
    }

    public function onLineItemDataWhenUngroupedDataProvider(): array
    {
        return [
            'when isGrouped is false' => ['context' => ['isGrouped' => false]],
            'when isGrouped is missing' => ['context' => []],
        ];
    }
}
