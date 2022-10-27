<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractScopedProductSelectTypeTest;

class ProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    protected function setUp(): void
    {
        $this->setDataParameters(['scope' => 'rfp']);
        $this->setConfigPath('oro_rfp.backend_product_visibility');

        parent::setUp();
        $this->loadFixtures([LoadCategoryProductData::class]);
    }
}
