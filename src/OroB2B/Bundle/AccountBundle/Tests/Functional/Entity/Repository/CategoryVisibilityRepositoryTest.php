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
        $actualItems = $this->repository->getVisibilityToAll($account);

        $root = $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
        $expectedItems = [
            [
                'categoryEntity' => $root,
                'to_all' => null,
                'to_account' => null,
                'to_group' => null,
            ],
            [
                'categoryEntity' => $this->getReference(LoadCategoryData::FIRST_LEVEL),
                'to_all' => null,
                'to_account' => null,
                'to_group' => AccountGroupCategoryVisibility::VISIBLE,

            ],
            [
                'categoryEntity' => $this->getReference(LoadCategoryData::SECOND_LEVEL1),
                'to_all' => null,
                'to_account' => null,
                'to_group' => AccountGroupCategoryVisibility::HIDDEN,

            ],
            [

                'categoryEntity' => $this->getReference(LoadCategoryData::THIRD_LEVEL1),
                'to_all' => null,
                'to_account' => null,
                'to_group' => null,
            ],
            [
                'categoryEntity' => $this->getReference(LoadCategoryData::THIRD_LEVEL2),
                'to_all' => null,
                'to_account' => null,
                'to_group' => null,
            ]
        ];
        foreach ($expectedItems as $i => $expected) {
            $actual = $actualItems[$i];
            $this->assertEquals($expected['categoryEntity']->getId(), $actual['categoryEntity']->getId());
            $this->assertEquals($expected['to_all'], $actual['to_all']);
            $this->assertEquals($expected['to_account'], $actual['to_account']);
            $this->assertEquals($expected['to_group'], $actual['to_group']);
        }
    }

    /**
     * @return array
     */
    public function getVisibilityToAllDataProvider()
    {
        return [];
    }
}
