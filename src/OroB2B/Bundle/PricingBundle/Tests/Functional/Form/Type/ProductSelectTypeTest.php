<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractProductSelectTypeTest;
use OroB2B\Bundle\PricingBundle\EventListener\ProductSelectPriceListAwareListener;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductSelectTypeTest extends AbstractProductSelectTypeTest
{
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );

        $this->setDatagridIndexPath('oro_datagrid_index');
        $this->setSearchAutocompletePath('oro_form_autocomplete_search');

        $this->setDataParameters(['price_list' => ProductSelectPriceListAwareListener::DEFAULT_ACCOUNT_USER]);

    }

    /**
     * @return array
     */
    public function restrictionDataProvider()
    {
        return [
            [
                [],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                ],
            ],
        ];
    }
}
