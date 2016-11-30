<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractScopedProductSelectTypeTest;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'quote']);
        $this->setConfigPath('oro_sale.backend_product_visibility');

        parent::setUp();
        $this->loadFixtures([LoadCategoryProductData::class]);
    }
}
