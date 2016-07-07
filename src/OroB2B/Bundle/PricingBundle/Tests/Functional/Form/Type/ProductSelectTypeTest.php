<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Form\Type;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;

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
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );

        $this->setDatagridIndexPath('orob2b_frontend_datagrid_index');
        $this->setSearchAutocompletePath('orob2b_frontend_autocomplete_search');

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
                ],
            ],
        ];
    }
}
