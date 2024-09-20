<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractFrontendScopedProductSelectTypeTest;

/**
 * @dbIsolationPerTest
 */
class FrontendProductSelectTypeTest extends AbstractFrontendScopedProductSelectTypeTest
{
    protected function setUp(): void
    {
        $this->setDatagridName('products-select-grid-frontend');
        $this->setDataParameters(['scope' => 'rfp']);
        $this->setConfigPath('oro_rfp.frontend_product_visibility');

        parent::setUp();

        $this->getContainer()
            ->get('oro_website_search.indexer')
            ->resetIndex();

        $this->loadFixtures([
            LoadCategoryProductData::class,
            LoadFrontendProductData::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
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
                ]
            ],
        ];
    }
}
