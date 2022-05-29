<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\Helper;

use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryImportExportHelperData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class CategoryImportExportHelperTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadCategoryImportExportHelperData::class
        ]);
    }

    /**
     * @dataProvider findCategoryByPathDataProvider
     */
    public function testFindCategoryByPath(string $categoryPath, ?string $expectedCategory): void
    {
        $helper = new CategoryImportExportHelper(self::getContainer()->get('doctrine'));
        $this->assertSame(
            $expectedCategory ? $this->getReference($expectedCategory) : null,
            $helper->findCategoryByPath($categoryPath, $this->getReference(LoadOrganization::ORGANIZATION))
        );
    }

    public function findCategoryByPathDataProvider(): array
    {
        return [
            [
                'categoryPath' => LoadCategoryImportExportHelperData::FIRST_LEVEL,
                'expectedCategory' => LoadCategoryImportExportHelperData::FIRST_LEVEL,
            ],
            [
                'categoryPath' => 'category_1 / category_1_2 / category_1_2_3',
                'expectedCategory' => LoadCategoryImportExportHelperData::THIRD_LEVEL1,
            ],
            [
                'categoryPath' => 'category_1 / category_1_2 / category1//2//3',
                'expectedCategory' => LoadCategoryImportExportHelperData::THIRD_LEVEL2,
            ],
            [
                'categoryPath' => 'category_1 / category_1_2 / category1 // 2 // 3',
                'expectedCategory' => LoadCategoryImportExportHelperData::THIRD_LEVEL3,
            ],
            [
                'categoryPath' => 'category_1_2_3',
                'expectedCategory' => LoadCategoryImportExportHelperData::THIRD_LEVEL1,
            ],
            [
                'categoryPath' => 'non_existent',
                'expectedCategory' => null,
            ],
            [
                'categoryPath' => 'category_1 / non_existent',
                'expectedCategory' => null,
            ],
        ];
    }
}
