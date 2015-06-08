<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\ImportExport\Strategy;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceResetStrategy;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class ProductPriceResetStrategyTest extends WebTestCase
{
    /**
     * @var ProductPriceResetStrategy
     */
    protected $strategy;

    /**
     * @var StepExecutionProxyContext
     */
    protected $context;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'
            ]
        );

        $this->strategy = $this->getContainer()
            ->get('orob2b_pricing.importexport.strategy.product_price.reset');
        $this->stepExecution = new StepExecution('step', new JobExecution());
        $this->context = new StepExecutionProxyContext($this->stepExecution);
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(
            $this->getContainer()->getParameter('orob2b_pricing.entity.product_price.class')
        );
    }

    protected function tearDown()
    {
        unset($this->strategy, $this->context, $this->stepExecution);
    }

    public function testProcessResetPriceListProductPrices()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.1');

        $this->assertEquals(
            [
                $productPrice,
                $this->getReference('product_price.2'),
                $this->getReference('product_price.3')
            ],
            $this->getContainer()
                ->get('doctrine')
                ->getRepository('OroB2BPricingBundle:ProductPrice')
                ->findBy(['priceList' => $priceList->getId()])
        );

        $this->strategy->process($productPrice);

        $this->assertEmpty(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository('OroB2BPricingBundle:ProductPrice')
                ->findBy(['priceList' => $priceList->getId()])
        );

        // do not clear twice
        $newProductPrice = $this->createProductPrice();
        $this->getContainer()->get('doctrine')->getManager()->persist($newProductPrice);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->strategy->process($productPrice);

        $this->assertEquals(
            [$newProductPrice],
            $this->getContainer()
                ->get('doctrine')
                ->getRepository('OroB2BPricingBundle:ProductPrice')
                ->findBy(['priceList' => $priceList->getId()])
        );
    }

    /**
     * @return ProductPrice
     */
    protected function createProductPrice()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $price = Price::create(1.2, 'USD');
        $newProductPrice = new ProductPrice();
        $newProductPrice
            ->setPriceList($priceList)
            ->setProduct($product)
            ->setQuantity(1)
            ->setPrice($price);

        return $newProductPrice;
    }
}
