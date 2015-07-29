<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\CatalogBundle\EventListener\DatagridListener;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatagridListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->listener = new DatagridListener();
    }

    /**
     * @var array
     */
    protected $expectedTemplate = [
        'source' => [
            'query' => [
                'select' => [],
                'join' => [
                    'left' => [
                        [
                            'join' => 'OroB2BCatalogBundle:Category',
                            'alias' => 'productCategory',
                            'conditionType' => 'WITH',
                            'condition' => 'product MEMBER OF productCategory.products'
                        ],
                        [
                            'join' => 'productCategory.titles',
                            'alias' => 'categoryTitle'
                        ]
                    ]
                ],
                'where' => [
                    'and' => ['categoryTitle.locale IS NULL']
                ]
            ],
        ],
        'columns' => [
            DatagridListener::CATEGORY_COLUMN => [
                'label' => 'orob2b.catalog.category.entity_label'
            ]
        ],
        'sorters' => [
            'columns' => [
                DatagridListener::CATEGORY_COLUMN => [
                    'data_name' => DatagridListener::CATEGORY_COLUMN
                ]
            ],
        ],
        'filters' => [
            'columns' => [
                DatagridListener::CATEGORY_COLUMN => [
                    'type' => 'string',
                    'data_name' => 'categoryTitle.string'
                ]
            ],
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testOnBuildBeforeProductsSelect()
    {


        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeProductsSelect($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['select'] = ['categoryTitle.string as ' . DatagridListener::CATEGORY_COLUMN];

        $this->assertEquals($expected, $config->toArray());
    }
}
