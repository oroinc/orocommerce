<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Autocomlete;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'shopping_list']);
        $this->setConfigPath('oro_b2b_shopping_list.backend_product_visibility');

        parent::setUp();
    }
}
