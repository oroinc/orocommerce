<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

use OroB2B\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\AddProductsToNewMassAction;

class AddProductsToNewMassActionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetOptions()
    {
        $expected = [
            'frontend_type' => 'add-products-to-new-mass',
            'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
            'route' => 'orob2b_shopping_list_add_products_to_new_massaction',
            'frontend_options' => [
                'title' => 'orob2b.shoppinglist.widget.add_to_new_shopping_list',
                'regionEnabled' => false,
                'incrementalPosition' => false,
                'dialogOptions' => [
                    'modal' => true,
                    'resizable' => false,
                    'width' => 480,
                    'autoResize' => true,
                    'dialogClass' => 'shopping-list-dialog',
                ],
                'alias' => 'add_prodiucts_to_new_shopping_list_mass_action'
            ],
            'frontend_handle' => 'dialog'
        ];

        $options = $this->createMassAction()->getOptions()->toArray();

        foreach ($expected as $name => $params) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($params, $options[$name]);
        }
    }

    /**
     * @return AddProductsToNewMassAction
     */
    protected function createMassAction()
    {
        $actionConfiguration = ActionConfiguration::create([]);

        $massAction = new AddProductsToNewMassAction();
        $massAction->setOptions($actionConfiguration);

        return $massAction;
    }
}
