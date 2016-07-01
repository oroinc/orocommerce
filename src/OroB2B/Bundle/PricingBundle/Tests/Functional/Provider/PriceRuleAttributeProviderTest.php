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
        $fields = $this->provider->getAvailableRuleAttributes('OroB2BProductBundle:Product');
        $this->assertEquals(
            $fields,
            [
                'id' => [
                    'name' => 'id',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'integer'
                ]
            ]
        );
    }

    public function testGetAvailableConditionAttributes()
    {
        $fields = $this->provider->getAvailableConditionAttributes('OroB2BProductBundle:Product');
        $this->assertEquals(
            $fields,
            [
                'id' => [
                    'name' => 'id',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'integer',
                ],
                'sku' => [
                    'name' => 'sku',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'string',
                ],
                'hasVariants' => [
                    'name' => 'hasVariants',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'boolean',
                ],
                'status' => [
                    'name' => 'status',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'string',
                ],
                'variantFields' => [
                    'name' => 'variantFields',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'array',
                ],
                'createdAt' => [
                    'name' => 'createdAt',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'datetime',
                ],
                'updatedAt' => [
                    'name' => 'updatedAt',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                    'data_type' => 'datetime',
                ],
                'owner_id' => [
                    'name' => 'owner_id',
                    'type' => PriceRuleAttributeProvider::FIELD_TYPE_VIRTUAL,
                    'data_type' => 'dictionary',
                ],
            ]
        );
    }
}
