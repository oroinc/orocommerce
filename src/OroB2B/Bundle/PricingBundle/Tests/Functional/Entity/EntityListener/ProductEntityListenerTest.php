<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class,
            LoadPriceRuleLexemes::class
        ]);
    }

    /**
     * @dataProvider preUpdateDataProvider
     * @param $productName
     * @param array $expectedTriggersPriceLists
     */
    public function testPreUpdate($productName, array $expectedTriggersPriceLists)
    {
        // Check price rule triggers are empty
        $this->assertEmpty($this->getTriggers());

        // Change product status
        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class);

        /** @var Product $product */
        $product = $this->getReference($productName);
        $this->assertEquals(Product::STATUS_ENABLED, $product->getStatus());
        $product->setStatus(Product::STATUS_DISABLED);

        $em->persist($product);
        $em->flush();

        // Check price rule trigger was added
        $triggers = $this->getTriggers();
        $this->assertNotEmpty($triggers);

        // Check triggers product and price lists
        $expectedPriceLists = [];
        foreach ($expectedTriggersPriceLists as $expectedTriggersPriceList) {
            $expectedPriceLists[] = $this->getReference($expectedTriggersPriceList);
        }

        foreach ($triggers as $trigger) {
            $this->assertEquals($product, $trigger->getProduct());
            $this->assertContains($trigger->getPriceList(), $expectedPriceLists);
        }
    }

    /**
     * @return array
     */
    public function preUpdateDataProvider()
    {
        return [
            [
                'productName' => 'product.1',
                'expectedTriggersPriceLists' => ['price_list_1']
            ]
        ];
    }

    /**
     * @return PriceRuleChangeTrigger[]
     */
    protected function getTriggers()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(PriceRuleChangeTrigger::class)
            ->getRepository(PriceRuleChangeTrigger::class)
            ->findAll();
    }
}
