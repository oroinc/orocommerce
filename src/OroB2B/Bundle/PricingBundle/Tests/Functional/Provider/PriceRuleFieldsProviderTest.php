<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class PriceRuleFieldsProviderTest extends WebTestCase
{
    /**
     * @var PriceRuleFieldsProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([]);
        $this->provider = $this->getContainer()->get('orob2b_pricing.provider.price_rule_fields_provider');
    }

    /**
     * @dataProvider getFieldsDataProvider
     * @param string $class
     * @param bool $onlyNumerical
     * @param bool $withRelations
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testGetFiends($class, $onlyNumerical, $withRelations, array $expectedFields)
    {
        $fields = $this->provider->getFields($class, $onlyNumerical, $withRelations);
        $this->assertEquals($expectedFields, $fields);
    }

    /**
     * @return array
     */
    public function getFieldsDataProvider()
    {
        return [
            [Product::class, true, false, ['id']],
            [
                Product::class,
                false,
                true,
                [
                    'createdAt',
                    'hasVariants',
                    'id',
                    'sku',
                    'status',
                    'updatedAt',
                    'variantFields',
                    'descriptions',
                    'images',
                    'inventory_status',
                    'metaDescriptions',
                    'metaKeywords',
                    'metaTitles',
                    'names',
                    'organization',
                    'owner',
                    'variantLinks',
                    'shortDescriptions',
                    'primaryUnitPrecision',
                    'unitPrecisions',
                ]
            ],
            [
                Product::class,
                false,
                false,
                [
                    'createdAt',
                    'hasVariants',
                    'id',
                    'sku',
                    'status',
                    'updatedAt',
                    'variantFields',
                ]
            ],
        ];
    }
}
