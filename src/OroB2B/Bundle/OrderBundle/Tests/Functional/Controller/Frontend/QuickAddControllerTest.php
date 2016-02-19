<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend\QuickAddControllerTest as BaseControllerTest;

/**
 * @dbIsolation
 */
class QuickAddControllerTest extends BaseControllerTest
{
    /**
     * @return array
     */
    public function validationResultProvider()
    {
        return [
            'create order' => [
                'processorName' => 'orob2b_order_quick_add_processor',
                'routerName' => 'orob2b_order_frontend_create',
                'routerParams' => ['storage' => 1],
                'expectedMessage' => null
            ]
        ];
    }
}
