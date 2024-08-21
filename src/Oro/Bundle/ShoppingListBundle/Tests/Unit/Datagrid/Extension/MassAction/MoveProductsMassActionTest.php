<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\MoveProductsMassAction;
use PHPUnit\Framework\TestCase;

class MoveProductsMassActionTest extends TestCase
{
    public function testSetOptionsWhenHasFrontendFlag(): void
    {
        $massAction = new MoveProductsMassAction();
        $massAction->setOptions(
            ActionConfiguration::create(['confirmation' => true, 'frontend' => true, 'icon' => 'move'])
        );

        self::assertEquals(
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
                    'frontend' => true,
                    'launcherOptions' => [
                        'iconClassName' => 'move',
                    ],
                ]
            ),
            $massAction->getOptions()
        );
    }

    public function testSetOptionsWhenNoFrontendFlag(): void
    {
        $massAction = new MoveProductsMassAction();
        $massAction->setOptions(
            ActionConfiguration::create(['confirmation' => true, 'icon' => 'move'])
        );

        self::assertEquals(
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
                    'launcherOptions' => [
                        'iconClassName' => 'fa-move',
                    ],
                ]
            ),
            $massAction->getOptions()
        );
    }
}
