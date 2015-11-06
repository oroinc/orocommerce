<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractFrontendScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class FrontendProductSelectTypeTest extends AbstractFrontendScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'order']);
        $this->setConfigPath('oro_b2b_order.frontend_product_visibility');

        parent::setUp();
    }
}
