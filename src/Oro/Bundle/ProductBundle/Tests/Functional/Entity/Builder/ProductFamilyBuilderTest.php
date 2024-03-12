<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Builder;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class ProductFamilyBuilderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadOrganization::class]);
    }

    public function testGetFamilyAsNull()
    {
        $factory = self::getContainer()->get('oro_product.entity.builder.product_family');
        $this->assertNull($factory->getFamily());
    }

    public function testAddDefaultAttributeGroupsToNonCreatedFamily()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Attribute groups can only be added to an instance '
            . 'of Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily.'
        );

        $factory = self::getContainer()->get('oro_product.entity.builder.product_family');
        $factory->addDefaultAttributeGroups();
    }

    public function testCreateDefaultFamily()
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $factory = self::getContainer()->get('oro_product.entity.builder.product_family');
        $productFamily = $factory->createDefaultFamily($organization)->getFamily();

        $this->assertInstanceOf(AttributeFamily::class, $productFamily);
        $this->assertEquals($organization, $productFamily->getOwner());
        $this->assertEquals(Product::class, $productFamily->getEntityClass());
    }

    public function testAddDefaultAttributeGroups()
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $factory = self::getContainer()->get('oro_product.entity.builder.product_family');
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
