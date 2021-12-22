<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesWithRules;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductPriceImportStrategyTest extends WebTestCase
{
    private const VERSION = 100;

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

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $container = $this->getContainer();

        $this->strategy = new ProductPriceImportStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.configurable_import_strategy_helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_importexport.field.related_entity_state_helper')
        );

        $jobExecution = new JobExecution();
        $jobInstance = new JobInstance();
        $jobInstance->setRawConfiguration(['import' => ['importVersion' => self::VERSION]]);
        $jobExecution->setJobInstance($jobInstance);
        $this->stepExecution = new StepExecution('import', $jobExecution);
        $this->context = new StepExecutionProxyContext($this->stepExecution);
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(ProductPrice::class);
        $this->strategy->setOwnershipSetter($container->get('oro_organization.entity_ownership_associations_setter'));
    }

    public function testProcessLoadPriceAndProduct()
    {
        $this->loadFixtures([LoadProductPrices::class]);

        $productPrice = new ProductPrice();
        $productPrice->setQuantity(555);
        $this->setValue($productPrice, 'value', 1.2);
        $this->setValue($productPrice, 'currency', 'USD');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        /** @var Product $product */
        $product = $this->getReference('product-1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productObject = new Product();
        $productObject->setSku($product->getSku());

        $productPrice->setProduct($product);
        $productPrice->setUnit($unit);
        $productPrice->setPriceList($priceList);

        $importedProductPrice = $this->strategy->process($productPrice);

        $this->assertNotEmpty($importedProductPrice->getPrice());
        $this->assertEquals('USD', $importedProductPrice->getPrice()->getCurrency());
        $this->assertEquals(1.2, $importedProductPrice->getPrice()->getValue());
        $this->assertNotEmpty($importedProductPrice->getProduct());
        $this->assertEquals($product->getSku(), $importedProductPrice->getProduct()->getSku());
        $this->assertEquals(self::VERSION, $importedProductPrice->getVersion());
        $this->assertNull($importedProductPrice->getPriceRule());
    }

    public function testProcesLoadPriceAndProductWithPriceRule()
    {
        $this->loadFixtures([LoadProductPricesWithRules::class]);

        $productPrice = new ProductPrice();
        $productPrice->setQuantity(5);
        $this->setValue($productPrice, 'value', 1.2);
        $this->setValue($productPrice, 'currency', 'USD');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        /** @var Product $product */
        $product = $this->getReference('product-1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productObject = new Product();
        $productObject->setSku($product->getSku());

        $productPrice->setProduct($product);
        $productPrice->setUnit($unit);
        $productPrice->setPriceList($priceList);

        $importedProductPrice = $this->strategy->process($productPrice);

        $this->assertNotEmpty($importedProductPrice->getPrice());
        $this->assertEquals('USD', $importedProductPrice->getPrice()->getCurrency());
        $this->assertEquals(1.2, $importedProductPrice->getPrice()->getValue());
        $this->assertNotEmpty($importedProductPrice->getProduct());
        $this->assertEquals($product->getSku(), $importedProductPrice->getProduct()->getSku());
        $this->assertEquals(self::VERSION, $importedProductPrice->getVersion());
        $this->assertNull($importedProductPrice->getPriceRule());
    }

    /**
     * @param object $entity
     * @param string $property
     * @param mixed $value
     */
    protected function setValue($entity, $property, $value)
    {
        $this->getContainer()->get('oro_entity.helper.field_helper')->setObjectValue($entity, $property, $value);
    }
}
