<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

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
        $this->provider = $this->getContainer()->get('oro_pricing.provider.price_rule_fields_provider');
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
        $this->assertEquals(sort($expectedFields), sort($fields));
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
                    'category',
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
