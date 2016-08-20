<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class CategoryVisibilityRepositoryTest extends WebTestCase
{
    const ROOT_CATEGORY = 'Master Catalog';

    /**
     * @var CategoryVisibilityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroAccountBundle:Visibility\CategoryVisibility');

        $this->loadFixtures(['Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
    }

    /**
     * @dataProvider getCategoriesVisibilitiesDataProvider
     * @param array $expectedData
     */
    public function testGetCategoriesVisibilities(array $expectedData)
    {
        $this->assertVisibilities($expectedData, $this->repository->getCategoriesVisibilities());
    }

    /**
     * @return array
     */
    public function getCategoriesVisibilitiesDataProvider()
    {
        return [
            [
                'expectedData' => [
                    [
                        'category' => self::ROOT_CATEGORY,
                        'visibility' => null,
                        'category_parent' => null,
                    ],
                    [
                        'category' => LoadCategoryData::FIRST_LEVEL,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'category_parent' => self::ROOT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL1,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL2,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL1,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL1,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL2,
                        'visibility' => CategoryVisibility::HIDDEN,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL2,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL1,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL1,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL2,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL2,
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $expectedData
     * @param array $actualData
     * @param array $fields
     */
    protected function assertVisibilities(array $expectedData, array $actualData, array $fields = [])
    {
        $expectedData = $this->prepareRawExpectedData($expectedData);
        $this->assertCount(count($expectedData), $actualData);
        foreach ($actualData as $i => $actual) {
            $this->assertArrayHasKey($i, $expectedData);
            $expected = $expectedData[$i];
            $this->assertEquals($expected['category_id'], $actual['category_id']);
            $this->assertEquals($expected['category_parent_id'], $actual['category_parent_id']);
            $this->assertEquals($expected['visibility'], $actual['visibility']);
            foreach ($fields as $field) {
                $this->assertEquals($expected[$field], $actual[$field]);
            }
        }
    }

    /**
     * @param array $expectedData
     * @return array
     */
    protected function prepareRawExpectedData(array $expectedData)
    {
        foreach ($expectedData as &$item) {
            $item['category_id'] = $this->getCategoryId($item['category']);
            unset($item['category']);
            $item['category_parent_id'] = $this->getCategoryId($item['category_parent']);
            unset($item['category_parent']);
        }

        return $expectedData;
    }

    /**
     * @param string $reference
     * @return integer
     */
    protected function getCategoryId($reference)
    {
        if ($reference === self::ROOT_CATEGORY) {
            return $this->getContainer()->get('doctrine')->getRepository('OroCatalogBundle:Category')
                ->getMasterCatalogRoot()->getId();
        }

        return $reference ? $this->getReference($reference)->getId() : null;
    }
}
