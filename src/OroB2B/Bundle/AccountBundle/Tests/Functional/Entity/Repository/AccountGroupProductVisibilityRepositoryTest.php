<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityRepositoryTest extends WebTestCase
{
    /** @var AccountGroupProductVisibilityRepository */
    protected $repository;

    /** @var  RegistryInterface */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility'
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    public function testGetCategoryByAccountGroupProductVisibility()
    {
        $agpv = $this->registry->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(['product' => $this->getReference(LoadProductData::PRODUCT_1)]);
        $agpv->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $this->registry->getEntityManager()->flush();
        $categories = $this->repository->getCategoriesByAccountGroupProductVisibility();
        $this->assertCount(1, $categories);
        $this->assertEquals($categories[0], $this->getReference(LoadCategoryData::FIRST_LEVEL));
    }

    public function testGetAccountGroupsForCategoryType()
    {
        $this->assertCount(1, $this->repository->getAccountsGroupsForCategoryType());
    }
}
