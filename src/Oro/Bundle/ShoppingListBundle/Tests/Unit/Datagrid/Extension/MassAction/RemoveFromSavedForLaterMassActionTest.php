<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\RemoveFromSavedForLaterMassAction;
use PHPUnit\Framework\TestCase;

final class RemoveFromSavedForLaterMassActionTest extends TestCase
{
    public function testSetOptionsWithoutOptions(): void
    {
        $massAction = new RemoveFromSavedForLaterMassAction();
        $massAction->setOptions(ActionConfiguration::create([]));

        self::assertSame(
            [
                'handler' => 'oro_shopping_list.mass_action.remove_from_saved_for_later_handler',
                'frontend_type' => 'remove-from-saved-for-later-mass',
                'route' => 'oro_frontend_datagrid_mass_action',
                'route_parameters' => [],
                'frontend_handle' => 'ajax',
                'confirmation' => true,
                'allowedRequestTypes' => ['POST'],
                'requestType' => 'POST'
            ],
            $massAction->getOptions()->toArray()
        );
    }

    public function testSetOptionsWithOptions(): void
    {
        $massAction = new RemoveFromSavedForLaterMassAction();
        $massAction->setOptions(ActionConfiguration::create([
            'handler' => 'test_handler',
            'frontend_type' => 'test_frontend_type',
            'route' => 'test_route',
            'route_parameters' => ['test_route_parameters']
        ]));

        self::assertSame(
            [
                'handler' => 'test_handler',
                'frontend_type' => 'test_frontend_type',
                'route' => 'test_route',
                'route_parameters' => ['test_route_parameters'],
                'frontend_handle' => 'ajax',
                'confirmation' => true,
                'allowedRequestTypes' => ['POST'],
                'requestType' => 'POST'
            ],
            $massAction->getOptions()->toArray()
        );
    }
}
