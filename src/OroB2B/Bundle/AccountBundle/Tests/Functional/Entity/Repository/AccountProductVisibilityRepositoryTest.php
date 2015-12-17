<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;

/**
 * @dbIsolation
 */
class AccountProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /** @var AccountProductVisibilityRepository */
    protected $repository;

    /** @var  RegistryInterface */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository(
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    public function testGetCategoryByAccountProductVisibility()
    {
        $categories = $this->repository->getCategoriesByAccountProductVisibility();
        $this->assertCount(1, $categories);
        $this->assertEquals($this->getReference('category_1_5_6_7'), $categories[0]);
    }

    public function testGetAccountsForCategoryType()
    {
        $this->assertCount(1, $this->repository->getAccountsForCategoryType());
    }

    /**
     * {@inheritdoc}
     */
    public function setToDefaultWithoutCategoryDataProvider()
    {
        return [
            [
                'category' => LoadCategoryData::FOURTH_LEVEL2,
                'deletedCategoryProducts' => ['product.8'],
            ],
        ];
    }
}
