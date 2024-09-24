<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListMixedLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ComposedSkuStringFilterTest extends FrontendWebTestCase
{
    private const GRID_NAME = 'frontend-customer-user-shopping-list-grid';
    private const GROUPED_CONFIGURABLE_PRODUCT_SKU = LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU
        . ','
        . LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadShoppingListMixedLineItems::class,
        ]);
    }

    /**
     * @dataProvider getGridFiltersDataProvider
     */
    public function testGridFilters(
        array $filter,
        array $parameters,
        array $expectedProductSkus
    ): void {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $lineItemsDatagridData = $this->getDatagridData($shoppingList->getId(), $filter, $parameters);

        self::assertCount(count($expectedProductSkus), $lineItemsDatagridData);

        $actualProductSkus = $this->getActualProductSkus($lineItemsDatagridData);

        foreach ($expectedProductSkus as $expectedProductSku) {
            self::assertContains($expectedProductSku, $actualProductSkus);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getGridFiltersDataProvider(): array
    {
        return [
            'empty filters' => [
                'filter' => [],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'contains "d"' => [
                'filter' => [
                    '[composedSku][value]' => 'd',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'contains "kit"' => [
                'filter' => [
                    '[composedSku][value]' => 'kit',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'contains "product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'contains "product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'contains "1"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'contains "1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'contains "CHR(30)product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'contains "RoDuCt"' => [
                'filter' => [
                    '[composedSku][value]' => 'RoDuCt',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'not contains "d"' => [
                'filter' => [
                    '[composedSku][value]' => 'd',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                ],
            ],
            'not contains "kit"' => [
                'filter' => [
                    '[composedSku][value]' => 'kit',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'not contains "product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'not contains "product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'not contains "1"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'not contains "1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'not contains "CHR(30)product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'not contains "RoDuCt"' => [
                'filter' => [
                    '[composedSku][value]' => 'RoDuCt',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'is equal to "product-kit-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'is equal to "product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is equal to "CHR(30)product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is equal to "product-1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is equal to "CHR(30)product-1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is equal to "PrOdUcT-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'PrOdUcT-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'starts with "product-kit-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'starts with "product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'starts with "roduct"' => [
                'filter' => [
                    '[composedSku][value]' => 'roduct',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [],
            ],
            'starts with "CHR(30)product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'starts with "pRoDuCt"' => [
                'filter' => [
                    '[composedSku][value]' => 'pRoDuCt',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'ends with "product-kit-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'ends with "-1"' => [
                'filter' => [
                    '[composedSku][value]' => '-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'ends with "product-"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [],
            ],
            'ends with "-1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => '-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'ends with "rOdUcT-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'rOdUcT-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is any of ["product-kit-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'is any of ["product-kit-1", "product-1", "FIRSTVARIANT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1, product-1, FIRSTVARIANT',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                ],
            ],
            'is any of ["product-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is any of ["PrOdUcT-1", "fIrStVaRiAnT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'PrOdUcT-1, fIrStVaRiAnT',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                ],
            ],
            'is any of ["CHR(30)product-1CHR(30)"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'is not any of ["product-kit-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'is not any of ["product-kit-1", "product-1", "FIRSTVARIANT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1, product-1, FIRSTVARIANT',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU
                ],
            ],
            'is not any of ["product-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'is not any of ["PrOdUcT-1", "fIrStVaRiAnT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'PrOdUcT-1, fIrStVaRiAnT',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'is not any of ["CHR(30)product-1CHR(30)"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'is empty' => [
                'filter' => [
                    '[composedSku][value]' => '',
                    '[composedSku][type]' => FilterUtility::TYPE_EMPTY,
                ],
                'parameters' => [],
                'expectedProductSkus' => [],
            ],
            'is not empty' => [
                'filter' => [
                    '[composedSku][value]' => '',
                    '[composedSku][type]' => FilterUtility::TYPE_NOT_EMPTY,
                ],
                'parameters' => [],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            // Grouped Product Variants
            'grouped: empty filters' => [
                'filter' => [],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: contains "d"' => [
                'filter' => [
                    '[composedSku][value]' => 'd',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: contains "kit"' => [
                'filter' => [
                    '[composedSku][value]' => 'kit',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'grouped: contains "product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: contains "product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: contains "1"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: contains "1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: contains "CHR(30)product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: contains "RoDuCt"' => [
                'filter' => [
                    '[composedSku][value]' => 'RoDuCt',
                    '[composedSku][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: not contains "d"' => [
                'filter' => [
                    '[composedSku][value]' => 'd',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [],
            ],
            'grouped: not contains "kit"' => [
                'filter' => [
                    '[composedSku][value]' => 'kit',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: not contains "product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: not contains "product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: not contains "1"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: not contains "1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => '1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: not contains "CHR(30)product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: not contains "RoDuCt"' => [
                'filter' => [
                    '[composedSku][value]' => 'RoDuCt',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_CONTAINS,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: is equal to "product-kit-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'grouped: is equal to "product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is equal to "CHR(30)product-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is equal to "product-1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is equal to "CHR(30)product-1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is equal to "PrOdUcT-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'PrOdUcT-1',
                    '[composedSku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: starts with "product-kit-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'grouped: starts with "product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: starts with "roduct"' => [
                'filter' => [
                    '[composedSku][value]' => 'roduct',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [],
            ],
            'grouped: starts with "CHR(30)product"' => [
                'filter' => [
                    '[composedSku][value]' => 'product',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: starts with "pRoDuCt"' => [
                'filter' => [
                    '[composedSku][value]' => 'pRoDuCt',
                    '[composedSku][type]' => TextFilterType::TYPE_STARTS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: ends with "product-kit-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'grouped: ends with "-1"' => [
                'filter' => [
                    '[composedSku][value]' => '-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: ends with "product-"' => [
                'filter' => [
                    '[composedSku][value]' => 'product-',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [],
            ],
            'grouped: ends with "-1CHR(30)"' => [
                'filter' => [
                    '[composedSku][value]' => '-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: ends with "rOdUcT-1"' => [
                'filter' => [
                    '[composedSku][value]' => 'rOdUcT-1',
                    '[composedSku][type]' => TextFilterType::TYPE_ENDS_WITH,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is any of ["product-kit-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                ],
            ],
            'grouped: is any of ["product-kit-1", "product-1", "FIRSTVARIANT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1, product-1, FIRSTVARIANT',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: is any of ["product-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is any of ["PrOdUcT-1", "fIrStVaRiAnT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'PrOdUcT-1, fIrStVaRiAnT',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: is any of ["CHR(30)product-1CHR(30)"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                ],
            ],
            'grouped: is not any of ["product-kit-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: is not any of ["product-kit-1", "product-1", "FIRSTVARIANT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-kit-1, product-1, FIRSTVARIANT',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: is not any of ["product-1"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: is not any of ["PrOdUcT-1", "fIrStVaRiAnT"]' => [
                'filter' => [
                    '[composedSku][value]' => 'PrOdUcT-1, fIrStVaRiAnT',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'grouped: is not any of ["CHR(30)product-1CHR(30)"]' => [
                'filter' => [
                    '[composedSku][value]' => 'product-1',
                    '[composedSku][type]' => TextFilterType::TYPE_NOT_IN,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
            'grouped: is empty' => [
                'filter' => [
                    '[composedSku][value]' => '',
                    '[composedSku][type]' => FilterUtility::TYPE_EMPTY,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [],
            ],
            'grouped: is not empty' => [
                'filter' => [
                    '[composedSku][value]' => '',
                    '[composedSku][type]' => FilterUtility::TYPE_NOT_EMPTY,
                ],
                'parameters' => [
                    '[group]' => true,
                ],
                'expectedProductSkus' => [
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    self::GROUPED_CONFIGURABLE_PRODUCT_SKU,
                ],
            ],
        ];
    }

    private function getDatagridData(
        string $shoppingListId,
        array $filterValues = [],
        array $parameterValues = []
    ): array {
        $filters = [
            self::GRID_NAME . '[shopping_list_id]' => $shoppingListId,
        ];
        foreach ($filterValues as $filterName => $value) {
            $filters[self::GRID_NAME . '[_filter]' . $filterName] = $value;
        }
        foreach ($parameterValues as $parameterName => $value) {
            $filters[self::GRID_NAME . '[_parameters]' . $parameterName] = $value;
        }

        $filters[self::GRID_NAME . '[_sort_by][id]'] = 'ASC';
        $filters[self::GRID_NAME . '[_pager][_page]'] = 1;
        $filters[self::GRID_NAME . '[_pager][_per_page]'] = 25;

        $response = $this->client->requestFrontendGrid(self::GRID_NAME, $filters, true);
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $responseContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $responseContent['data'];
    }

    private function getActualProductSkus(array $lineItemsDatagridData): array
    {
        $actualProductSkus = [];
        foreach ($lineItemsDatagridData as $product) {
            if ($product['sku'] === null) {
                $variantSkus = [];
                foreach ($product['subData'] as $variant) {
                    $variantSkus[] = $variant['sku'];
                }

                $actualProductSkus[] = implode(',', $variantSkus);
            } else {
                $actualProductSkus[] = $product['sku'];
            }
        }

        return $actualProductSkus;
    }
}
