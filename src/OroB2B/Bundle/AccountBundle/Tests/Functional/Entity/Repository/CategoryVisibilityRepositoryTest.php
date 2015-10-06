<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\CategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class CategoryVisibilityRepositoryTest extends WebTestCase
{
    /**
     * @var CategoryVisibilityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities',
            ]
        );
    }

    /**
     * dataProvider getVisibilityToAllDataProvider
     */
    public function testGetVisibilityToAll()
    {
        $account = $this->getReference('account.level_1.3');
        /** @var array $actual */
        $actual = $this->repository->getVisibilityToAll($account);

        $root = $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
        $expected = [
            [
                'id' => $root->getId(),
                'parent_category' => null,
                'to_all' => null,
                'to_account' => null,
                'to_group' => null,
            ],
            [
                'id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
                'parent_category' => $root->getId(),
                'to_all' => null,
                'to_account' => null,
                'to_group' => AccountGroupCategoryVisibility::VISIBLE,

            ],
            [
                'id' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
                'parent_category' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
                'to_all' => null,
                'to_account' => null,
                'to_group' =>AccountGroupCategoryVisibility::HIDDEN,

            ],
            [

                'id' => $this->getReference(LoadCategoryData::THIRD_LEVEL1)->getId(),
                'parent_category' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
                'to_all' => null,
                'to_account' => null,
                'to_group' => null,
            ],
            [
                'id' => $this->getReference(LoadCategoryData::THIRD_LEVEL2)->getId(),
                'parent_category' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
                'to_all' => null,
                'to_account' => null,
                'to_group' => null,
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getVisibilityToAllDataProvider()
    {
        return [];
    }
}
