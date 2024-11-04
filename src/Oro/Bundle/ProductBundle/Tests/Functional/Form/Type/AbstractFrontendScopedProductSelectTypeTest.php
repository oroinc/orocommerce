<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;

abstract class AbstractFrontendScopedProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    use MysqlVersionCheckTrait;

    private AbstractPlatform $platform;

    #[\Override]
    protected function setUp(): void
    {
        $this->setDatagridIndexPath('oro_frontend_datagrid_index');
        $this->setSearchAutocompletePath('oro_frontend_autocomplete_search');

        parent::setUp();

        $this->initClient();
        $this->configScopeName = 'website';
        $this->configScopeIdentifier = $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $this->platform = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();
    }

    /**
     * @dataProvider restrictionSelectDataProvider
     */
    #[\Override]
    public function testSearchRestriction(array $restrictionParams, array $expectedProducts)
    {
        if ($this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $this->markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

        parent::testSearchRestriction($restrictionParams, $expectedProducts);
    }

    #[\Override]
    public function restrictionSelectDataProvider(): array
    {
        return [
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.out_of_stock'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3
                ],
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.discontinued']],
                'expectedProducts' => []
            ],
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.discontinued'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                ]
            ],
        ];
    }

    #[\Override]
    public function restrictionGridDataProvider(): array
    {
        return [
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.out_of_stock'
                    ]
                ],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['prod_inventory_status.discontinued']],
                'expectedProducts' => [],
            ],
            [
                [
                    'availableInventoryStatuses' => [
                        'prod_inventory_status.in_stock',
                        'prod_inventory_status.discontinued'
                    ]
                ],
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
