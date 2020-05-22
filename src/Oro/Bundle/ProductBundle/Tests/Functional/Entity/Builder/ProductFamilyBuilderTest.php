<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Builder;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductFamilyBuilderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testGetFamilyAsNull()
    {
        $factory = $this->getContainer()->get('oro_product.entity.builder.product_family');
        $this->assertNull($factory->getFamily());
    }

    public function testAddDefaultAttributeGroupsToNonCreatedFamily()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Attribute groups can only be added to an instance '
            . 'of Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily.'
        );

        $factory = $this->getContainer()->get('oro_product.entity.builder.product_family');
        $factory->addDefaultAttributeGroups();
    }

    public function testCreateDefaultFamily()
    {
        $organization = $this->getContainer()->get('doctrine')
            ->getRepository(Organization::class)
            ->getFirst();

        $factory = $this->getContainer()->get('oro_product.entity.builder.product_family');
        $productFamily = $factory->createDefaultFamily($organization)->getFamily();

        $this->assertInstanceOf(AttributeFamily::class, $productFamily);
        $this->assertEquals($organization, $productFamily->getOwner());
        $this->assertEquals(Product::class, $productFamily->getEntityClass());
    }

    public function testAddDefaultAttributeGroups()
    {
        $organization = $this->getContainer()->get('doctrine')
            ->getRepository(Organization::class)
            ->getFirst();

        $factory = $this->getContainer()->get('oro_product.entity.builder.product_family');
        $productFamily = $factory->createDefaultFamily($organization)
            ->addDefaultAttributeGroups()
            ->getFamily();

        $this->assertInstanceOf(AttributeFamily::class, $productFamily);
        $this->assertEquals(
            ['general', 'inventory', 'images', 'prices', 'seo'],
            array_keys($productFamily->getAttributeGroups()->toArray())
        );
    }
}
