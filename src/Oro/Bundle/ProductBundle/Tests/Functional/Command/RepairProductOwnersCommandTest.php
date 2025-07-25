<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadSecondOrganizationWithBusinessUnit;

class RepairProductOwnersCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductData::class,
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadSecondOrganizationWithBusinessUnit::class,
        ]);
    }

    public function testExecuteWithCorrectProductOwners(): void
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $ownerId = $product->getOwner()->getId();
        $managerRegistry = self::getContainer()->get('doctrine');
        $em = $managerRegistry->getManagerForClass(Product::class);

        $output = self::runCommand('oro:product:repair-owners');
        self::assertEmpty($output);

        $product = $em->find(Product::class, $product->getId());
        self::assertEquals($ownerId, $product->getOwner()->getId());
    }

    public function testExecuteWithIncorrectProductOwners(): void
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $secondOrganization = $this->getReference(LoadSecondOrganizationWithBusinessUnit::SECOND_ORGANIZATION);
        $secondOrganizationBusinessUnit = $this->getReference(
            LoadSecondOrganizationWithBusinessUnit::SECOND_ORGANIZATION_BUSINESS_UNIT
        );
        $product->setOrganization($secondOrganization);
        $managerRegistry = self::getContainer()->get('doctrine');
        $em = $managerRegistry->getManagerForClass(Product::class);
        $em->persist($product);
        $em->flush();

        $output = self::runCommand('oro:product:repair-owners');
        self::assertEquals(sprintf('Owner updated for products: %s', $product->getSku()), $output);

        $product = $em->find(Product::class, $product->getId());
        self::assertEquals($secondOrganizationBusinessUnit, $product->getOwner());
    }

    public function testExecuteWithEmptyOrganizationBusinessUnits(): void
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $ownerId = $product->getOwner()->getId();
        $secondOrganization = new Organization();
        $secondOrganization->setName('SecondOrganization');

        $product->setOrganization($secondOrganization);
        $managerRegistry = self::getContainer()->get('doctrine');
        $em = $managerRegistry->getManagerForClass(Product::class);
        $em->persist($secondOrganization);
        $em->persist($product);
        $em->flush();

        $output = self::runCommand('oro:product:repair-owners');
        self::assertEquals(
            sprintf(
                '<warning>Owner not updated for products(no business units in product organization): %s</warning>',
                $product->getSku()
            ),
            $output
        );

        $product = $em->find(Product::class, $product->getId());
        self::assertEquals($ownerId, $product->getOwner()->getId());
    }
}
