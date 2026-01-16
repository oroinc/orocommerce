<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction\SaveForLaterMassAction;
use PHPUnit\Framework\TestCase;

final class SaveForLaterMassActionTest extends TestCase
{
    public function testSetOptionsWithoutOptions(): void
    {
        $massAction = new SaveForLaterMassAction();
        $massAction->setOptions(ActionConfiguration::create([]));

        self::assertSame(
            [
                'handler' => 'oro_shopping_list.mass_action.save_for_later_handler',
                'frontend_type' => 'save-for-later-mass',
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
        $massAction = new SaveForLaterMassAction();
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
