<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListConfigurableLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

class MatrixGridOrderProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadShoppingListConfigurableLineItems::class,
            ]
        );
    }

    public function testGetTotalsQuantityPriceWontCallAllProductVariantPrecisionsHydration()
    {
        $literPrecisionId = $this->addAdditionalUnitPrecisionToProductVariant(
            LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
            LoadProductUnits::LITER
        );
        self::getContainer()->get('doctrine')->getManager()->clear();

        $this->loginUser(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        self::getContainer()
            ->get('oro_shopping_list.layout.data_provider.matrix_grid_order')
            ->getTotalsQuantityPrice(
                [$this->getReference(LoadConfigurableProductWithVariants::CONFIGURABLE_SKU)]
            );

        /** @var UnitOfWork $uow */
        $uow = self::getContainer()->get('doctrine')->getManager()->getUnitOfWork();
        $identityMap = ReflectionUtil::getPropertyValue($uow, 'identityMap');
        self::assertTrue(empty($identityMap[ProductUnitPrecision::class][$literPrecisionId]));
    }

    private function addAdditionalUnitPrecisionToProductVariant(string $productRef, string $productUnitRef): int
    {
        /** @var Product $product */
        $product = self::getReference($productRef);
        $literPrecision = (new ProductUnitPrecision())
            ->setUnit(self::getReference($productUnitRef))
            ->setPrecision(1);
        $product->addAdditionalUnitPrecision($literPrecision);
        self::getContainer()->get('doctrine')->getManagerForClass(Product::class)->flush();

        return $literPrecision->getId();
    }
}
