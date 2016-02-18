<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\EventListener\ProductDatagridViewsListener;

class ProductDatagridViewsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductDatagridViewsListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    public function setUp()
    {
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->request = new Request();

        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $this->listener = new ProductDatagridViewsListener($requestStack);
    }

    /**
     * @dataProvider onPreBuildDataProvider
     *
     * @param string $viewName
     * @param array $expectedConfig
     */
    public function testOnPreBuild($viewName, array $expectedConfig)
    {
        $gridName = 'grid-name';
        $this->request->query->set($gridName, [ProductDatagridViewsListener::GRID_THEME_PARAM_NAME => $viewName]);
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
                ProductDatagridViewsListener::VIEW_GRID,
                [
                    'options' => [
                        'templates' => ['row' => 'grid-name-grid-row-template']
                    ],
                    'name' => 'grid-name',

                ]
            ],
            [
                ProductDatagridViewsListener::VIEW_LIST,
                [
                    'options' => [
                        'templates' => ['row' => 'grid-name-list-row-template']
                    ],
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
                ProductDatagridViewsListener::VIEW_TILES,
                [
                    'options' => [
                        'templates' => ['row' => 'grid-name-tiles-row-template']
                    ],
                    'name' => 'grid-name',
                    'source' => [
                        'query'=> [
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
