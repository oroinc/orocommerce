<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'rfp']);
        $this->setConfigPath('oro_b2b_rfp.backend_product_visibility');

        parent::setUp();
    }
}
