<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

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
            'rfp create' => [
                'processorName' => 'orob2b_rfp_quick_add_processor',
                'routerName' => 'orob2b_rfp_frontend_request_create',
                'routerParams' => ['storage' => 1],
                'expectedMessage' => null
            ]
        ];
    }
}
