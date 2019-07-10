<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Factory;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

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
        $this->assertEquals($organization, $productFamily->getOrganization());
        $this->assertNull($productFamily->getOwner());
        $this->assertEquals(Product::class, $productFamily->getEntityClass());
    }

    public function testCreateDefaultFamilyWithOwner()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $userRepository = $doctrine->getRepository(User::class);
        $organizationRepository = $doctrine->getRepository(Organization::class);

        $organization = $organizationRepository->getFirst();
        $owner = $userRepository->findOneByUsername('admin');

        $factory = $this->getContainer()->get('oro_product.entity.factory.product_family');
        $productFamily = $factory->createDefaultFamily($organization, $owner);

        $this->assertInstanceOf(AttributeFamily::class, $productFamily);
        $this->assertEquals($owner, $productFamily->getOwner());
    }

    public function testCreateDefaultFamilyWithOwnerFromToken()
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $userRepository = $doctrine->getRepository(User::class);
        $organizationRepository = $doctrine->getRepository(Organization::class);

        $organization = $organizationRepository->getFirst();
        $user = $userRepository->findOneByUsername('admin');
        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'main',
            $user->getOrganization()
        );
        $container->get('security.token_storage')->setToken($token);

        $factory = $this->getContainer()->get('oro_product.entity.factory.product_family');
        $productFamily = $factory->createDefaultFamily($organization);

        $this->assertInstanceOf(AttributeFamily::class, $productFamily);
        $this->assertEquals($user, $productFamily->getOwner());
    }
}
