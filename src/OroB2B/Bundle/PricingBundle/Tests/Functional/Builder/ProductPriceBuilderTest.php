<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Builder;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductPriceBuilderTest extends WebTestCase
{
    /**
     * @var ProductPriceBuilder
     */
    protected $productPriceBuilder;

    /**
     * @var EntityManager
     */
    protected $manager;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadProductData::class,
            ]
        );
        $this->productPriceBuilder = $this->getContainer()->get('orob2b_pricing.builder.product_price_builder');
        $this->manager = $this->getContainer()->get('doctrine')->getManager();
    }

    public function testBuildByRule()
    {
        $this->markTestSkipped('Will be fixed is scope of BB-3626');

        $priceList = new PriceList();
        $priceList->setName('Test');
        $this->manager->persist($priceList);
        $this->manager->flush($priceList);
        
        $rule = new PriceRule();
        $rule->setPriority(1);
        $rule->setCurrency('USD');
        $rule->setQuantity(1);
        $rule->setProductUnit($this->getReference('product_unit.milliliter'));
        $rule->setPriceList($priceList);

        $productsIds[] = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $productsIds[] = $this->getReference(LoadProductData::PRODUCT_2)->getId();

        $rule->setRuleCondition('product.id IN (' . implode(',', $productsIds) . ')');
        $rule->setRule('777');
        $this->manager->persist($rule);
        $this->manager->flush($rule);

        $this->productPriceBuilder->buildByRule($rule);
        $prices = $this->manager->getRepository(ProductPrice::class)->findBy(['priceList' => $priceList]);
        $this->assertNotEmpty($prices);
        foreach ($prices as $price) {
            $this->assertEquals($rule, $price->getPriceRule());
            $this->assertTrue(in_array($price->getProduct()->getId(), $productsIds));
            $this->assertSame($price->getPrice()->getCurrency(), 'USD');
            $this->assertSame((int)$price->getPrice()->getValue(), (int)777);
            $this->assertSame((int)$price->getQuantity(), 1);
            $this->assertSame($price->getUnit()->getCode(), $this->getReference('product_unit.milliliter')->getCode());
        }
    }
}
