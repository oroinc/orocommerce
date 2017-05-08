<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractFrontendScopedProductSelectTypeTest;

/**
 * @dbIsolationPerTest
 */
class FrontendProductSelectTypeTest extends AbstractFrontendScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDataParameters(['scope' => 'rfp']);
        $this->setConfigPath('oro_rfp.frontend_product_visibility');

        parent::setUp();

        $this->getContainer()
            ->get('oro_website_search.indexer')
            ->resetIndex();

        $this->loadFixtures([LoadCategoryProductData::class, LoadFrontendProductData::class]);
    }
}
