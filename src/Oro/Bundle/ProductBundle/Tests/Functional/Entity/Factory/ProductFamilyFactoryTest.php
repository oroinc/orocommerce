<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Factory;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductFamilyFactoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreateDefaultFamily()
    {
        $organization = $this->getContainer()->get('doctrine')
            ->getRepository(Organization::class)
            ->getFirst();

        $factory = $this->getContainer()->get('oro_product.entity.factory.product_family');
        $productFamily = $factory->createDefaultFamily($organization);

        $this->assertInstanceOf(AttributeFamily::class, $productFamily);
        $this->assertEquals($organization, $productFamily->getOwner());
        $this->assertEquals(Product::class, $productFamily->getEntityClass());
    }
}
