<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend\QuickAddControllerTest as BaseControllerTest;

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
                'processorName' => 'oro_rfp_quick_add_processor',
                'routerName' => 'oro_rfp_frontend_request_create',
                'routerParams' => ['storage' => 1],
                'expectedMessage' => null
            ]
        ];
    }
}
