<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Provider\ProductKitItemUnitPrecisionProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductKitItemUnitPrecisionProviderTest extends WebTestCase
{
    private ProductKitItemUnitPrecisionProvider $provider;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadProductKitData::class]);

        $this->provider = self::getContainer()->get('oro_product.provider.product_kit_item_unit_precision');
    }

    public function testGetUnitPrecisionByKitItemWhenNewEntity(): void
    {
        self::assertEquals(0, $this->provider->getUnitPrecisionByKitItem(new ProductKitItem()));
    }

    public function testGetUnitPrecisionByKitItemWhenPrecision0(): void
    {
        self::assertEquals(
            $this->getReference('product_unit_precision.product-1.milliliter')->getPrecision(),
            $this->provider->getUnitPrecisionByKitItem(
                $this->getReference(LoadProductKitData::PRODUCT_KIT_1)->getKitItems()->get(0)
            )
        );
    }

    public function testGetUnitPrecisionByKitItemWhenPrecisionNot0(): void
    {
        self::assertEquals(
            $this->getReference('product_unit_precision.product-1.liter')->getPrecision(),
            $this->provider->getUnitPrecisionByKitItem(
                $this->getReference(LoadProductKitData::PRODUCT_KIT_3)->getKitItems()->get(0)
            )
        );
    }
}
