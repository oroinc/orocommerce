<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassAction;

class AddProductsMassActionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetOptions()
    {
        $actionConfiguration = ActionConfiguration::create(['confirmation' => true]);
        $massAction = (new AddProductsMassAction())->setOptions($actionConfiguration);
        $options = $massAction->getOptions();

        $this->assertEquals('add-products-mass', $options['frontend_type']);
        $this->assertEquals('oro_shopping_list.mass_action.add_products_handler', $options['handler']);
        $this->assertEquals('oro_shopping_list_add_products_massaction', $options['route']);
        $this->assertEmpty($options['route_parameters']);
        $this->assertEquals('ajax', $options['frontend_handle']);
    }
}
