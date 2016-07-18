<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\TriggersFiller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;

/**
 * @dbIsolation
 */
class PriceRuleChangeTriggersFillerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadPriceRules::class
            ]
        );
    }

    public function testCreateFillerWithProduct()
    {
        /** @var PriceRule $priceRule */
        $priceRule = $this->getReference(LoadPriceRules::PRICE_RULE_1);

        /** @var Product $product */
        $product = $this->getReference('product.1');

        // Check trigger is absent
        $this->assertNull($this->getRuleTrigger($priceRule, $product));

        $this->getContainer()
            ->get('orob2b_pricing.triggers_filler.price_rule_change_triggers_filler')
            ->createTrigger($priceRule, $product);

        // Check trigger added
        $this->assertNotNull($this->getRuleTrigger($priceRule, $product));
    }

    public function testCreateFillerWithoutProduct()
    {
        /** @var PriceRule $priceRule */
        $priceRule = $this->getReference(LoadPriceRules::PRICE_RULE_1);

        // Check trigger is absent
        $this->assertNull($this->getRuleTrigger($priceRule));

        $this->getContainer()
            ->get('orob2b_pricing.triggers_filler.price_rule_change_triggers_filler')
            ->createTrigger($priceRule);

        // Check trigger added
        $this->assertNotNull($this->getRuleTrigger($priceRule));
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     * @return PriceRuleChangeTrigger|null
     */
    protected function getRuleTrigger(PriceRule $priceRule, $product = null)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceRuleChangeTrigger')
            ->getRepository('OroB2BPricingBundle:PriceRuleChangeTrigger')
            ->findOneBy([
                'priceRule' => $priceRule,
                'product' => $product
            ]);
    }
}
