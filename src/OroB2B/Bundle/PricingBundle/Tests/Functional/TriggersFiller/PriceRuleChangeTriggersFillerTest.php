<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\TriggersFiller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleChangeTriggersFiller;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class PriceRuleChangeTriggersFillerTest extends WebTestCase
{
    /**
     * @var PriceRuleChangeTriggersFiller
     */
    protected $filler;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules'
            ]
        );
        
        $this->filler = $this->getContainer()
            ->get('orob2b_pricing.triggers_filler.price_rule_change_triggers_filler');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset($this->filler);
    }

    public function testCreateFillerWithProduct()
    {
        /** @var PriceRule $priceRule */
        $priceRule = $this->getReference('price_list_1_rule');

        /** @var Product $product */
        $product = $this->getReference('product.1');

        // Check trigger is absent
        $this->assertRuleTriggerNotExist($priceRule, $product);

        $this->filler->createTrigger($priceRule, $product);

        // Check trigger added
        $this->assertRuleTriggerExist($priceRule, $product);
    }

    public function testCreateFillerWithoutProduct()
    {
        /** @var PriceRule $priceRule */
        $priceRule = $this->getReference('price_list_1_rule');

        // Check trigger is absent
        $this->assertRuleTriggerNotExist($priceRule);

        $this->filler->createTrigger($priceRule);

        // Check trigger added
        $this->assertRuleTriggerExist($priceRule);
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    public function assertRuleTriggerExist(PriceRule $priceRule, $product = null)
    {
        $this->assertNotNull($this->getRuleTrigger($priceRule, $product));
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    public function assertRuleTriggerNotExist(PriceRule $priceRule, $product = null)
    {
        $this->assertNull($this->getRuleTrigger($priceRule, $product));
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     * @return PriceRuleChangeTrigger|null
     */
    protected function getRuleTrigger(PriceRule $priceRule, $product)
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
