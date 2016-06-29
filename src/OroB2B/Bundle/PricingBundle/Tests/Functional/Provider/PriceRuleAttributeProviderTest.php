<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleAttributeProvider;

/**
 * @dbIsolation
 */
class PriceRuleAttributeProviderTest extends WebTestCase
{
    /**
     * @var PriceRuleAttributeProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([]);
        $this->provider = $this->getContainer()->get('orob2b_pricing.provider.price_rule_attribute_provider');
    }

    public function testGetAvailableRuleAttributes()
    {
        $fields = $this->provider->getAvailableRuleAttributes();
        $this->assertEquals($fields['OroB2BProductBundle:Product'], ['id']);
    }

    public function testGetAvailableConditionAttributes()
    {
        $fields = $this->provider->getAvailableConditionAttributes();
        $this->assertEquals(
            $fields['OroB2BProductBundle:Product'],
            [
                'id',
                'sku',
                'hasVariants',
                'status',
                'variantFields',
                'createdAt',
                'updatedAt',
                'owner_id',
            ]
        );
    }
}
