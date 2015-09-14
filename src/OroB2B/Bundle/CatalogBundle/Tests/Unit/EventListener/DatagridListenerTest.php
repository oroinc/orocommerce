<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\CatalogBundle\EventListener\DatagridListener;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected static $expectedTemplate = [
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
    ];

    public function testOnBuildBeforeProductsSelect()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $listener = new DatagridListener();
        $listener->onBuildBeforeProductsSelect($event);

        $expected = self::$expectedTemplate;
        $expected['source']['query']['select'] = ['categoryTitle.string as ' . DatagridListener::CATEGORY_COLUMN];

        $this->assertEquals($expected, $config->toArray());
    }
}
