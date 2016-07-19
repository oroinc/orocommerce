<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\TriggersFiller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
                LoadPriceLists::class
            ]
        );
    }

    public function testCreateFillerWithProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        /** @var Product $product */
        $product = $this->getReference('product.1');

        // Check trigger is absent
        $this->assertNull($this->getRuleTrigger($priceList, $product));

        $this->getContainer()
            ->get('orob2b_pricing.triggers_filler.price_rule_change_triggers_filler')
            ->createTrigger($priceList, $product);

        // Check trigger added
        $this->assertNotNull($this->getRuleTrigger($priceList, $product));
    }

    public function testCreateFillerWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        // Check trigger is absent
        $this->assertNull($this->getRuleTrigger($priceList));

        $this->getContainer()
            ->get('orob2b_pricing.triggers_filler.price_rule_change_triggers_filler')
            ->createTrigger($priceList);

        // Check trigger added
        $this->assertNotNull($this->getRuleTrigger($priceList));
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     * @return PriceRuleChangeTrigger|null
     */
    protected function getRuleTrigger(PriceList $priceList, $product = null)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceRuleChangeTrigger')
            ->getRepository('OroB2BPricingBundle:PriceRuleChangeTrigger')
            ->findOneBy([
                'priceList' => $priceList,
                'product' => $product
            ]);
    }
}
