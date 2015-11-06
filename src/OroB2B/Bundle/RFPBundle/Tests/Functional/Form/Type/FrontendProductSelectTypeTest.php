<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractFrontendScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class FrontendProductSelectTypeTest extends AbstractFrontendScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'rfp']);
        $this->setConfigPath('oro_b2b_rfp.frontend_product_visibility');

        parent::setUp();
    }
}
