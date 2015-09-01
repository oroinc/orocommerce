<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\ShoppingListBundle\EventListener\DatagridListener;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected static $expectedTemplate = [
        'properties' => [
            'add_to_shopping_list_link' => [
                'type' => 'url',
                'route' => 'orob2b_shopping_list_line_item_frontend_add_widget',
                'params' => [
                    'productId' => 'id',
                ],
            ],
        ],
        'actions' => [
            'add_to_shopping_list' => [
                'type' => 'dialog',
                'label' => 'orob2b.shoppinglist.product.add_to_shopping_list.label',
                'link' => 'add_to_shopping_list_link',
                'icon' => 'shopping-cart',
                'acl_resource' => 'orob2b_shopping_list_line_item_frontend_add',
                'widgetOptions' => [
                    'options' => [
                        'dialogOptions' => [
                            'title' => 'Add To Shopping List'
                        ]
                    ]
                ]
            ],
        ],
        'mass_actions' => [
            'addproducts' => [
                'type' => 'addproducts',
                'entity_name' => '%orob2b_product.product.class%',
                'data_identifier' => 'product.id',
                'label' => 'orob2b.shoppinglist.product.add_to_shopping_list.label',
                'acl_resource' => 'orob2b_shopping_list_line_item_frontend_add',
            ],
        ],
    ];

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
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
    }

    /**
     * Method testOnBuildBeforeFrontendProducts
     */
    public function testOnBuildBeforeFrontendProducts()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeFrontendProducts($event);

        $this->assertEquals(self::$expectedTemplate, $config->toArray());
    }
}
