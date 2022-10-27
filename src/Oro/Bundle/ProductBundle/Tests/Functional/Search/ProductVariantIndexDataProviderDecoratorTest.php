<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema\OroFrontendTestFrameworkBundleInstaller;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductVariantIndexDataProviderDecoratorTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadConfigurableProductWithVariants::class]);
    }

    /**
     * @param string $allTextValue
     * @param array $expectedSkus
     *
     * @dataProvider allTextVariantDataProvider
     */
    public function testAllTextVariantSearch($allTextValue, array $expectedSkus)
    {
        if ($this->getContainer()->getParameter('database_driver') === DatabaseDriverInterface::DRIVER_MYSQL) {
            $this->markTestSkipped(
                'Fulltext search does not work inside the transaction for MySQL. ' .
                'https://dev.mysql.com/doc/refman/5.6/en/innodb-fulltext-index.html#innodb-fulltext-index-transaction'
            );
        }

        $response = $this->client->requestFrontendGrid(
            'frontend-product-search-grid',
            [
                'frontend-product-search-grid[_filter][all_text][type]' => TextFilterType::TYPE_CONTAINS,
                'frontend-product-search-grid[_filter][all_text][value]' => $allTextValue,
            ],
            true
        );

        $this->assertEquals($expectedSkus, $this->getSkusFromResponse($response));
    }

    /**
     * @return array
     */
    public function allTextVariantDataProvider()
    {
        return [
            'configurable by SKU' => [
                'all_text_value' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU
                ],
            ],
            'first variant by SKU' => [
                'all_text_value' => LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                ],
            ],
            'first variant by enum name' => [
                'all_text_value' => 'Good',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                ],
            ],
            'first variant by multienum name' => [
                'all_text_value' => 'First',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                ],
            ],
            'second variant by SKU' => [
                'all_text_value' => LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU

                ],
            ],
            'second variant by enum name' => [
                'all_text_value' => 'Better',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'second variant by multienum name' => [
                'all_text_value' => 'Third',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'both variants by multienum name' => [
                'all_text_value' => 'Second',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'unused enum name' => [
                'all_text_value' => 'The best',
                'expected_skus' => [],
            ],
            'unused multienum name' => [
                'all_text_value' => 'Fourth',
                'expected_skus' => [],
            ],
        ];
    }

    /**
     * @param string $enumName
     * @param array $expectedSkus
     *
     * @dataProvider enumVariantDataProvider
     */
    public function testEnumVariantSearch($enumName, array $expectedSkus)
    {
        $variantClassName = ExtendHelper::buildEnumValueClassName(
            OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_CODE
        );
        /** @var AbstractEnumValue $variantEnum */
        $variantEnum = self::getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository($variantClassName)
            ->findOneBy(['name' => $enumName]);

        $response = $this->client->requestFrontendGrid(
            'frontend-product-search-grid',
            [
                'frontend-product-search-grid[_filter][test_variant_field][value][]'
                    => $variantEnum->getId(),
            ],
            true
        );

        $skus = $this->getSkusFromResponse($response);
        $this->assertEquals($expectedSkus, $skus);
    }

    /**
     * @return array
     */
    public function enumVariantDataProvider()
    {
        return [
            'first variant' => [
                'enum_name' => 'Good',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                ],
            ],
            'second variant' => [
                'enum_name' => 'Better',
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU
                ],
            ],
            'unused enum' => [
                'enum_name' => 'The best',
                'expected_skus' => [],
            ],
        ];
    }

    /**
     * @param Response $response
     * @return array
     */
    private function getSkusFromResponse(Response $response)
    {
        $actualSkus = [];
        $products = \json_decode($response->getContent(), true)['data'];
        foreach ($products as $product) {
            $actualSkus[] = $product['sku'];
        }
        sort($actualSkus);

        return $actualSkus;
    }

    /**
     * @dataProvider multiEnumVariantDataProvider
     */
    public function testMultiEnumVariantSearch(array $multiEnumCodes, array $expectedSkus)
    {
        $filters = [];
        foreach ($multiEnumCodes as $key => $code) {
            $filters["frontend-product-search-grid[_filter][multienum_field][value][$key]"] = $code;
        }

        $response = $this->client->requestFrontendGrid(
            'frontend-product-search-grid',
            $filters,
            true
        );

        $this->assertEquals($expectedSkus, $this->getSkusFromResponse($response));
    }

    /**
     * @return array
     */
    public function multiEnumVariantDataProvider()
    {
        return [
            'only first option' => [
                'multienum_codes' => ['first'],
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                ],
            ],
            'only second option' => [
                'multienum_codes' => ['second'],
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'only third option' => [
                'multienum_codes' => ['third'],
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'first and third options' => [
                'multienum_codes' => ['first', 'third'],
                'expected_skus' => [
                    LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
                    LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
                    LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
                ],
            ],
            'only fourth option' => [
                'multienum_codes' => ['fourth'],
                'expected_skus' => [],
            ],
        ];
    }
}
