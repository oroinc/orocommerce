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
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductDatagridListener
     */
    protected $listener;

    /**
     * @var DataGridThemeHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $themeHelper;

    /**
     * @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $imagePlaceholderProvider;

    protected function setUp(): void
    {
        $this->themeHelper = $this->createMock(DataGridThemeHelper::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $this->listener = new FrontendProductDatagridListener(
            $this->themeHelper,
            $this->imagePlaceholderProvider
        );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     *
     * @param string $themeName
     * @param array $expectedConfig
     */
    public function testOnPreBuild($themeName, array $expectedConfig)
    {
        $gridName = 'grid-name';
        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $config = DatagridConfiguration::createNamed($gridName, []);
        $params = new ParameterBag();
        $event  = new PreBuild($config, $params);
        $this->listener->onPreBuild($event);
        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function onPreBuildDataProvider()
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
     *
     * @param string $themeName
     * @param array $data
     * @param array $expectedData
     */
    public function testOnResultAfter(
        $themeName,
        array $data,
        array $expectedData
    ) {
        $records = [];
        foreach ($data as $record) {
            $records[] = new ResultRecord($record);
        }

        /**
         * @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event
         */
        $event = $this->getMockBuilder(SearchResultAfter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn($records);

        /**
         * @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $datagrid
         */
        $datagrid = $this->getMockBuilder(Datagrid::class)
            ->disableOriginalConstructor()->getMock();

        $gridName = 'grid-name';
        $datagrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $this->imagePlaceholderProvider->expects($this->any())
            ->method('getPath')
            ->with('product_medium')
            ->willReturn('/some/test/url.png');

        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->listener->onResultAfter($event);
        foreach ($expectedData as $expectedRecord) {
            $record = current($records);
            $this->assertEquals($expectedRecord['id'], $record->getValue('id'));
            $this->assertEquals($expectedRecord['image'], $record->getValue('image'));
            $this->assertEquals($expectedRecord['expectedUnits'], $record->getValue('product_units'));
            next($records);
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
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
                        'image'         => '/image/1/medium',
                        'expectedUnits' => [
                            'each' => 3,
                            'set' => 0
                        ]
                    ],
                    [
                        'id'            => 2,
                        'image'         => '/some/test/url.png',
                        'expectedUnits' => [
                            'bottle' => 0
                        ]
                    ],
                    [
                        'id'            => 3,
                        'image'         => '/image/3/medium',
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
                        'image'         => '/image/1/medium',
                        'expectedUnits' => []
                    ],
                    [
                        'id'            => 2,
                        'image'         => '/some/test/url.png',
                        'expectedUnits' => []
                    ],
                    [
                        'id'            => 3,
                        'image'         => '/image/3/medium',
                        'expectedUnits' => []
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider themesWithoutImageDataProvider
     *
     * @param string $themeName
     */
    public function testOnResultAfterViewWithoutImage($themeName)
    {
        /** @var SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(SearchResultAfter::class)
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn([]);

        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->getMockBuilder(Datagrid::class)
            ->disableOriginalConstructor()->getMock();

        $gridName = 'grid-name';
        $datagrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->listener->onResultAfter($event);
    }

    /**
     * @return array
     */
    public function themesWithoutImageDataProvider()
    {
        return [
            ['themeName' => DataGridThemeHelper::VIEW_LIST],
        ];
    }
}
