<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridLineItemsDataPreloadListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class DatagridLineItemsDataPreloadListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreloadingManager|\PHPUnit\Framework\MockObject\MockObject */
    private $preloadingManger;

    /** @var DatagridLineItemsDataPreloadListener */
    private $listener;

    protected function setUp(): void
    {
        $this->preloadingManger = $this->createMock(PreloadingManager::class);
        $this->listener = new DatagridLineItemsDataPreloadListener($this->preloadingManger);
    }

    public function testOnLineItemDataWhenGrouped(): void
    {
        $lineItemSimple = $this->createMock(ProductLineItemInterface::class);
        $lineItemConfigurable = $this->createMock(ProductLineItemInterface::class);
        $lineItemConfigurable
            ->expects($this->any())
            ->method('getParentProduct')
            ->willReturn($this->createMock(Product::class));

        $this->preloadingManger
            ->expects($this->exactly(2))
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
                ]
            );

        $event = new DatagridLineItemsDataEvent(
            [$lineItemSimple, $lineItemConfigurable],
            $this->createMock(Datagrid::class),
            ['isGrouped' => true]
        );
        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider onLineItemDataWhenUngroupedDataProvider
     */
    public function testOnLineItemDataWhenUngrouped(array $context): void
    {
        $lineItemSimple = $this->createMock(ProductLineItemInterface::class);
        $lineItemConfigurable = $this->createMock(ProductLineItemInterface::class);
        $lineItemConfigurable
            ->expects($this->any())
            ->method('getParentProduct')
            ->willReturn($this->createMock(Product::class));

        $this->preloadingManger
            ->expects($this->exactly(2))
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
                ]
            );

        $event = new DatagridLineItemsDataEvent(
            [$lineItemSimple, $lineItemConfigurable],
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
