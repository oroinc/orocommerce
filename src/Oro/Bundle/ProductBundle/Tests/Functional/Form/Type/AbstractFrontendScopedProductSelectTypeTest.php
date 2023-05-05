<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;

abstract class AbstractFrontendScopedProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    use MysqlVersionCheckTrait;

    protected function setUp(): void
    {
        $this->setDatagridIndexPath('oro_frontend_datagrid_index');
        $this->setSearchAutocompletePath('oro_frontend_autocomplete_search');

        parent::setUp();

        $this->configScopeName = 'website';
        $this->configScopeIdentifier = $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $this->platform = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();
    }

    /**
     * @dataProvider restrictionSelectDataProvider
     */
    public function testSearchRestriction(array $restrictionParams, array $expectedProducts)
    {
        if ($this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $this->markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

        parent::testSearchRestriction($restrictionParams, $expectedProducts);
    }

    /**
     * {@inheritDoc}
     */
    public function restrictionSelectDataProvider(): array
    {
        return [
            [
                ['availableInventoryStatuses' => ['in_stock', 'out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3
                ],
            ],
            [
                ['availableInventoryStatuses' => ['discontinued']],
                'expectedProducts' => []
            ],
            [
                ['availableInventoryStatuses' => ['in_stock', 'discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                ]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function restrictionGridDataProvider(): array
    {
        return [
            [
                ['availableInventoryStatuses' => ['in_stock', 'out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['discontinued']],
                'expectedProducts' => [],
            ],
            [
                ['availableInventoryStatuses' => ['in_stock', 'discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ],
            ],
        ];
    }
}
