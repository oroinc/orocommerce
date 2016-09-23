<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Form\Type;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\Form\Type\AbstractProductSelectTypeTest;
use Oro\Bundle\PricingBundle\EventListener\ProductSelectPriceListAwareListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );

        $this->setDatagridIndexPath('oro_frontend_datagrid_index');
        $this->setSearchAutocompletePath('oro_frontend_autocomplete_search');

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
