<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class AccountProductVisibilityRepositoryTest extends WebTestCase
{
    /** @var AccountProductVisibilityRepository */
    protected $repository;

    /** @var  RegistryInterface */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    public function testGetCategoryByAccountProductVisibility()
    {
        $apv = $this->registry->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->findOneBy(['product' => $this->getReference(LoadProductData::PRODUCT_1)]);
        $apv->setVisibility(AccountProductVisibility::CATEGORY);

        $this->registry->getEntityManager()->flush();
        $categories = $this->repository->getCategoriesByAccountProductVisibility();
        $this->assertCount(1, $categories);
        $this->assertEquals($categories[0], $this->getReference(LoadCategoryData::FIRST_LEVEL));
    }

    public function testGetAccountsForCategoryType()
    {
        $this->assertCount(1, $this->repository->getAccountsForCategoryType());
    }
}
