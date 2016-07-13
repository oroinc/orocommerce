<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class PriceRuleAttributeProviderTest extends WebTestCase
{
    /**
     * @var PriceRuleFieldsProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([]);
        $this->provider = $this->getContainer()->get('orob2b_pricing.provider.price_rule_attribute_provider');
    }

    /**
     * @throws \Exception
     */
    public function testGetAvailableRuleAttributes()
    {
        $fields = $this->provider->getAvailableRuleAttributes(Product::class);
        $this->assertEquals($fields, ['id']);
    }

    /**
     * @throws \Exception
     */
    public function testGetAvailableConditionAttributes()
    {
        $fields = $this->provider->getAvailableFields(Product::class);
        $this->assertEquals(
            $fields,
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
        );
    }
}
