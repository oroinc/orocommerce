<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

class CategoryVisibilityRepositoryTest extends WebTestCase
{
    use CatalogTrait;

    private const ROOT_CATEGORY = 'All Products';

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryVisibilityData::class]);
    }

    /**
     * @dataProvider getCategoriesVisibilitiesDataProvider
     */
    public function testGetCategoriesVisibilities(array $expectedData)
    {
        $categoriesVisibilities = $this->getRepository()->getCategoriesVisibilities();
        $this->assertVisibilities($expectedData, $categoriesVisibilities);
    }

    public function getCategoriesVisibilitiesDataProvider(): array
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

    private function getRepository(): CategoryVisibilityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CategoryVisibility::class);
    }

    private function assertVisibilities(array $expectedData, array $actualData, array $fields = [])
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

    private function prepareRawExpectedData(array $expectedData): array
    {
        foreach ($expectedData as &$item) {
            $item['category_id'] = $this->getCategoryId($item['category']);
            unset($item['category']);
            $item['category_parent_id'] = $this->getCategoryId($item['category_parent']);
            unset($item['category_parent']);
        }

        return $expectedData;
    }

    private function getCategoryId(?string $reference): ?int
    {
        if ($reference === self::ROOT_CATEGORY) {
            return $this->getRootCategory()->getId();
        }

        return $reference ? $this->getReference($reference)->getId() : null;
    }
}
