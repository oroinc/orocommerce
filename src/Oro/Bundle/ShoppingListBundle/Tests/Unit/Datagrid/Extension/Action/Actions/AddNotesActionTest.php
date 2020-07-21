<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\Action\Actions\AddNotesAction;

class AddNotesActionTest extends \PHPUnit\Framework\TestCase
{
    public function testSetOptions(): void
    {
        $massAction = new AddNotesAction();
        $massAction->setOptions(ActionConfiguration::create(['confirmation' => true]));

        $this->assertEquals(
            ActionConfiguration::create(
                [
                    'confirmation' => false,
                ]
            ),
            $massAction->getOptions()
        );
    }
}
