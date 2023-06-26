<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductKitRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductKitData::class,
        ]);
    }

    private function getRepository(): ProductRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Product::class);
    }

    public function testGetProductKitsByRequiredProduct(): void
    {
        self::assertCount(3, $this->getRepository()->getProductKitsByRequiredProduct(
            $this->getReference('product-1')
        ));
    }
}
