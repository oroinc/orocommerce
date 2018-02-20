<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductItemType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class RequestProductItemTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testBlockPrefix()
    {
        $formType = new RequestProductItemType();
        static::assertEquals('oro_rfp_frontend_request_product_item', $formType->getBlockPrefix());
    }
}
