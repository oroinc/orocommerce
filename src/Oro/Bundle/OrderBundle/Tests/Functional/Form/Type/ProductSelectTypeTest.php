<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'order']);
        $this->setConfigPath('oro_order.backend_product_visibility');

        parent::setUp();
        $this->loadFixtures([LoadCategoryProductData::class]);
    }
}
