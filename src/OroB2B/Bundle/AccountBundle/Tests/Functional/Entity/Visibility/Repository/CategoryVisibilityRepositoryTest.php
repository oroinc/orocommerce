<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
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

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
    }

    /**
     * dataProvider getVisibilityToAllDataProvider
     */
    public function testGetVisibilityToAll()
    {
        /** @var array $actualItems */
        $actualItems = $this->repository->getCategoriesVisibilitiesQueryBuilder()->addOrderBy('c.left')
            ->getQuery()->execute();

        $root = $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
        $expectedItems = [
            [
                'category_id' => $root->getId(),
                'visibility' => null,
                'category_parent_id' => null,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
                'visibility' => CategoryVisibility::VISIBLE,
                'category_parent_id' => $root->getId(),
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
                'visibility' => null,
                'category_parent_id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL2)->getId(),
                'visibility' => null,
                'category_parent_id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL1)->getId(),
                'visibility' => CategoryVisibility::VISIBLE,
                'category_parent_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL2)->getId(),
                'visibility' => CategoryVisibility::HIDDEN,
                'category_parent_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL2)->getId(),
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::FOURTH_LEVEL1)->getId(),
                'visibility' => null,
                'category_parent_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL1)->getId(),
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::FOURTH_LEVEL2)->getId(),
                'visibility' => null,
                'category_parent_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL2)->getId(),
            ]
        ];
        $this->assertCount(count($expectedItems), $actualItems);
        foreach ($actualItems as $i => $actual) {
            $this->assertArrayHasKey($i, $expectedItems);
            $expected = $expectedItems[$i];
            $this->assertEquals($expected['category_id'], $actual['category_id']);
            $this->assertEquals($expected['category_parent_id'], $actual['category_parent_id']);
            $this->assertEquals($expected['visibility'], $actual['visibility']);
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
