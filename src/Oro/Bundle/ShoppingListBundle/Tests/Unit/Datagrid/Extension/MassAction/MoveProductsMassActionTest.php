<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\MoveProductsMassAction;

class MoveProductsMassActionTest extends \PHPUnit\Framework\TestCase
{
    public function testSetOptions(): void
    {
        $massAction = new MoveProductsMassAction();
        $massAction->setOptions(ActionConfiguration::create(['confirmation' => true]));

        $this->assertEquals(
            ActionConfiguration::create(
                [
                    'confirmation' => false,
                    'handler' => 'oro_shopping_list.mass_action.move_products_handler',
                    'frontend_type' => 'move-products-mass',
                    'route' => 'oro_shopping_list_frontend_move_mass_action',
                    'route_parameters' => [],
                    'frontend_handle' => 'dialog',
                    'selectedElement' => 'input[name="selected"]:checked',
                    'allowedRequestTypes' => ['POST'],
                    'requestType' => 'POST',
                ]
            ),
            $massAction->getOptions()
        );
    }
}
