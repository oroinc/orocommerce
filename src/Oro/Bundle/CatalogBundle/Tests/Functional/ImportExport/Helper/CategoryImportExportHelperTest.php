<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\Helper;

use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryImportExportHelperData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryImportExportHelperTest extends WebTestCase
{
    /** @var CategoryImportExportHelper */
    private $helper;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadCategoryImportExportHelperData::class,
            ]
        );

        $this->helper = $this->getContainer()->get('oro_catalog.importexport.helper.category_import_export');
    }

    /**
     * @dataProvider findCategoryByPathDataProvider
     *
     * @param string $categoryPath
     * @param string|null $expectedCategory
     */
    public function testFindCategoryByPath(string $categoryPath, ?string $expectedCategory): void
    {
        $this->assertSame(
            $expectedCategory ? $this->getReference($expectedCategory) : null,
            $this->helper->findCategoryByPath($categoryPath)
        );
    }

    /**
     * @return array
     */
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
