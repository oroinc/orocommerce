<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\EventListener\ProductDatagridViewsListener;

class ProductDatagridViewsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductDatagridViewsListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DataGridThemeHelper
     */
    protected $themeHelper;

    public function setUp()
    {
        $this->themeHelper = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductDatagridViewsListener($this->themeHelper);
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
        $event = new PreBuild($config, $params);
        $this->listener->onPreBuild($event);
        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @return array
     */
    public function onPreBuildDataProvider()
    {
        return [
            [
                DataGridThemeHelper::VIEW_GRID,
                [
                    'name' => 'grid-name',

                ]
            ],
            [
                DataGridThemeHelper::VIEW_LIST,
                [
                    'name' => 'grid-name',
                    'source' => [
                        'query' => [
                            'select' => [
                                'productImage.filename as image',
                                'productDescriptions.string as description'
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.image',
                                        'alias' => 'productImage'
                                    ]
                                ],
                                'inner' => [
                                    [
                                        'join' => 'product.descriptions',
                                        'alias' => 'productDescriptions',
                                        'conditionType' => 'WITH',
                                        'condition' => 'productDescriptions.locale IS NULL'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                DataGridThemeHelper::VIEW_TILES,
                [
                    'name' => 'grid-name',
                    'source' => [
                        'query' => [
                            'select' => [
                                'productImage.filename as image',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.image',
                                        'alias' => 'productImage'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
