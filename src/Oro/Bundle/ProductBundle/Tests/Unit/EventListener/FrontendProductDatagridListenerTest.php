<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\UIBundle\Tools\UrlHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const NO_IMAGE_PATH = '/path/no_image.jpg';

    private DataGridThemeHelper|\PHPUnit\Framework\MockObject\MockObject $themeHelper;

    private ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject $imagePlaceholderProvider;

    private FrontendProductDatagridListener $listener;

    protected function setUp(): void
    {
        $this->themeHelper = $this->createMock(DataGridThemeHelper::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $urlHelper = $this->createMock(UrlHelper::class);
        $urlHelper
            ->expects(self::any())
            ->method('getAbsolutePath')
            ->willReturnCallback(static fn (string $path) => '/absolute' . $path);

        $this->listener = new FrontendProductDatagridListener(
            $this->themeHelper,
            $this->imagePlaceholderProvider,
            $urlHelper
        );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     */
    public function testOnPreBuild(string $themeName, array $expectedConfig)
    {
        $gridName = 'grid-name';
        $this->themeHelper->expects(self::any())
            ->method('getTheme')
            ->willReturn($themeName);

        $config = DatagridConfiguration::createNamed($gridName, []);
        $params = new ParameterBag();
        $event  = new PreBuild($config, $params);
        $this->listener->onPreBuild($event);
        self::assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onPreBuildDataProvider(): array
    {
        return [
            'list'  => [
                DataGridThemeHelper::VIEW_LIST,
                [
                    'name' => 'grid-name',
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ],
                        'hasImage' => [
                            'type' => 'field',
                            'frontend_type' => 'boolean',
                        ]
                    ],
                    'columns' => [
                        'image'=> ['label' => 'oro.product.image.label'],
                    ]
                ],
            ],
            'grid'  => [
                DataGridThemeHelper::VIEW_GRID,
                [
                    'name'       => 'grid-name',
                    'properties' => [
                        'product_units'    => [
                            'type'          => 'field',
                            'frontend_type' => 'row_array',
                        ],
                        'shortDescription' => [
                            'type'      => LocalizedValueProperty::NAME,
                            'data_name' => 'shortDescriptions',
                        ],
                        'hasImage' => [
                            'type' => 'field',
                            'frontend_type' => 'boolean',
                        ]
                    ],
                    'columns'    => [
                        'image'            => ['label' => 'oro.product.image.label'],
                        'shortDescription' => ['label' => 'oro.product.short_descriptions.label'],
                    ]
                ]
            ],
            'tiles' => [
                DataGridThemeHelper::VIEW_TILES,
                [
                    'name'       => 'grid-name',
                    'properties' => [
                        'product_units' => [
                            'type'          => 'field',
                            'frontend_type' => 'row_array',
                        ],
                        'hasImage' => [
                            'type' => 'field',
                            'frontend_type' => 'boolean',
                        ]
                    ],
                    'columns'    => [
                        'image' => ['label' => 'oro.product.image.label'],
                    ]
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(string $themeName, array $data, array $expectedData)
    {
        $records = [];
        foreach ($data as $record) {
            $records[] = new ResultRecord($record);
        }

        $event = $this->createMock(SearchResultAfter::class);
        $event->expects(self::once())
            ->method('getRecords')
            ->willReturn($records);

        $gridName = 'grid-name';
        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects(self::once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects(self::any())
            ->method('getTheme')
            ->willReturn($themeName);

        $this->imagePlaceholderProvider->expects(self::any())
            ->method('getPath')
            ->with('product_medium')
            ->willReturn(self::NO_IMAGE_PATH);

        $event->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->listener->onResultAfter($event);
        foreach ($expectedData as $expectedRecord) {
            /** @var ResultRecord $record */
            $record = current($records);
            self::assertEquals($expectedRecord['id'], $record->getValue('id'));
            self::assertSame($expectedRecord['hasImage'], $record->getValue('hasImage'));
            self::assertEquals($expectedRecord['image'], $record->getValue('image'));
            self::assertEquals($expectedRecord['expectedUnits'], $record->getValue('product_units'));
            next($records);
        }
    }

    public function onResultAfterDataProvider(): array
    {
        return [
            [
                'themeName'    => DataGridThemeHelper::VIEW_TILES,
                'records'      => [
                    [
                        'id'                   => 1,
                        'image_product_medium' => '/image/1/medium',
                        'image_product_large'  => '/image/1/large',
                        'product_units'        => serialize(['each' => 3, 'set' => 0])
                    ],
                    ['id' => 2, 'product_units' => serialize(['bottle' => 0])],
                    [
                        'id'                   => 3,
                        'image_product_medium' => '/image/3/medium',
                        'image_product_large'  => '/image/3/large'
                    ],
                ],
                'expectedData' => [
                    [
                        'id'            => 1,
                        'hasImage'      => true,
                        'image'         => '/absolute/image/1/medium',
                        'expectedUnits' => [
                            'each' => 3,
                            'set' => 0
                        ]
                    ],
                    [
                        'id'            => 2,
                        'hasImage'      => false,
                        'image'         => self::NO_IMAGE_PATH,
                        'expectedUnits' => [
                            'bottle' => 0
                        ]
                    ],
                    [
                        'id'            => 3,
                        'hasImage'      => true,
                        'image'         => '/absolute/image/3/medium',
                        'expectedUnits' => []
                    ],
                ],
            ],
            [
                'themeName'    => DataGridThemeHelper::VIEW_TILES,
                'records'      => [
                    [
                        'id'                   => 1,
                        'image_product_medium' => '/image/1/medium',
                        'image_product_large'  => '/image/1/large',
                    ],
                    ['id' => 2],
                    [
                        'id'                   => 3,
                        'image_product_medium' => '/image/3/medium',
                        'image_product_large'  => '/image/3/large',
                    ],
                ],
                'expectedData' => [
                    [
                        'id'            => 1,
                        'hasImage'      => true,
                        'image'         => '/absolute/image/1/medium',
                        'expectedUnits' => []
                    ],
                    [
                        'id'            => 2,
                        'hasImage'      => false,
                        'image'         => self::NO_IMAGE_PATH,
                        'expectedUnits' => []
                    ],
                    [
                        'id'            => 3,
                        'hasImage'      => true,
                        'image'         => '/absolute/image/3/medium',
                        'expectedUnits' => []
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider themesWithoutImageDataProvider
     */
    public function testOnResultAfterViewWithoutImage(string $themeName)
    {
        $event = $this->createMock(SearchResultAfter::class);
        $event->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $gridName = 'grid-name';
        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects(self::once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects(self::any())
            ->method('getTheme')
            ->willReturn($themeName);

        $event->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->listener->onResultAfter($event);
    }

    public function themesWithoutImageDataProvider(): array
    {
        return [
            ['themeName' => DataGridThemeHelper::VIEW_LIST],
        ];
    }
}
