<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Writer;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceImportStrategy;
use Oro\Bundle\PricingBundle\ImportExport\Writer\ProductPriceWriter;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceWriterTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadProductUnits::class,
            LoadPriceListToProductWithoutPrices::class,
        ]);
    }

    public function testHashArrayClearsOnWrite()
    {
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance(new JobInstance());
        $stepExecution = new StepExecution('step', $jobExecution);

        /** @var ContextRegistry $contextRegistry */
        $contextRegistry = $this->getContainer()->get('oro_importexport.context_registry');
        $context = $contextRegistry->getByStepExecution($stepExecution);
        $context->setValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH, [md5('hash1'), md5('hash2')]);
        $this->assertNotEmpty($context->getValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH));

        $container = $this->getContainer();

        $writer = new ProductPriceWriter(
            $container->get('doctrine'),
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.context_registry'),
            $container->get('oro_integration.logger.strategy'),
            $container->get('oro_pricing.manager.price_manager'),
            $container->get('oro_platform.optional_listeners.manager')
        );
        $writer->disableListener('oro_pricing.entity_listener.product_price_cpl');
        $writer->disableListener('oro_pricing.entity_listener.price_list_to_product');
        $writer->disableListener('oro_website.indexation_request_listener');
        $writer->setStepExecution($stepExecution);

        $price = $this->createPrice();

        $writer->write([$price]);
        $this->assertEmptyMessages(ResolveCombinedPriceByPriceListTopic::getName());
        $this->assertEmptyMessages(ResolvePriceRulesTopic::getName());
        $value = $context->getValue(ProductPriceImportStrategy::PROCESSED_ENTITIES_HASH);
        $this->assertEmpty($value);
    }

    private function createPrice(): ProductPrice
    {
        $priceList = $this->getReference('price_list_2');
        $product = $this->getReference('product-3');
        $price = new ProductPrice();
        $price->setPrice(Price::create(1, 'USD'));
        $price->setProduct($product);
        $price->setPriceList($priceList);
        $price->setQuantity(1);
        $unit = $this->getReference(LoadProductUnits::BOX);
        $price->setUnit($unit);

        return $price;
    }
}
