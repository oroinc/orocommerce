<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\VirtualFields;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class VirtualFieldsProductDecoratorTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testVirtualRelations()
    {
        $factory = $this->getContainer()->get('oro_product.virtual_fields.decorator_factory');

        $productOne = $this->getReference(LoadProductData::PRODUCT_1);
        $productTwo = $this->getReference(LoadProductData::PRODUCT_2);

        $decoratedProduct = $factory->createDecoratedProduct([$productOne, $productTwo], $productOne);

        $this->assertTrue($decoratedProduct->category instanceof Category);
    }
}
