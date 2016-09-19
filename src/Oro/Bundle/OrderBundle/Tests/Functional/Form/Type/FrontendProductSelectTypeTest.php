<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractFrontendScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class FrontendProductSelectTypeTest extends AbstractFrontendScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'order']);
        $this->setConfigPath('oro_order.frontend_product_visibility');

        parent::setUp();
    }
}
