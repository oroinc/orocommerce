<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\ImportExport\Strategy;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class ProductPriceImportStrategyTest extends WebTestCase
{
    /**
     * @var ProductPriceImportStrategy
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
            ->get('orob2b_pricing.importexport.strategy.product_price.add_or_replace');
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

    public function testProcessLoadPriceAndProduct()
    {
        $productPrice = new ProductPrice();
        $productPrice->setQuantity(555);
        $this->setValue($productPrice, 'value', 1.2);
        $this->setValue($productPrice, 'currency', 'USD');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        /** @var Product $product */
        $product = $this->getReference('product.1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productObject = new Product();
        $productObject->setSku($product->getSku());

        $productPrice->setProduct($product);
        $productPrice->setUnit($unit);
        $productPrice->setPriceList($priceList);

        $this->strategy->process($productPrice);

        $this->assertNotEmpty($productPrice->getPrice());
        $this->assertEquals('USD', $productPrice->getPrice()->getCurrency());
        $this->assertEquals(1.2, $productPrice->getPrice()->getValue());
        $this->assertNotEmpty($productPrice->getProduct());
        $this->assertEquals($product->getSku(), $productPrice->getProduct()->getSku());
    }

    /**
     * @param object $entity
     * @param string $property
     * @param mixed $value
     */
    protected function setValue($entity, $property, $value)
    {
        $this->getContainer()->get('oro_importexport.field.field_helper')->setObjectValue($entity, $property, $value);
    }
}
