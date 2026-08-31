<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Writer;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
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
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;

/**
 * @dbIsolationPerTest
 */
class ProductPriceWriterTest extends WebTestCase
{
    use MessageQueueAssertTrait;
    use ConfigManagerAwareTestTrait;

    private const VERSION = 12345;

    private ?string $savedStorage = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadProductUnits::class,
            LoadPriceListToProductWithoutPrices::class,
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restorePricingStorage();

        parent::tearDown();
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

    /**
     * Prices imported without a version, e.g. when the import version option is not set,
     * are reindexed by the written chunk.
     */
    public function testFlatStorageReindexOnWriteOfPricesWithoutVersion(): void
    {
        $this->enableFlatPricingStorage();

        $this->clearMessageCollector();
        $this->createWriter()->write($this->createPrices());

        $this->assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        $messages = self::getTopicSentMessages(WebsiteSearchReindexTopic::getName());
        self::assertEqualsCanonicalizing(
            [$this->getReference('product-3')->getId(), $this->getReference('product-4')->getId()],
            $messages[0]['message']['context']['entityIds']
        );
    }

    /**
     * Prices imported with a version are reindexed in bulk by ResolveVersionedFlatPriceTopic,
     * so the writer must not produce per price reindexation messages.
     */
    public function testFlatStorageNoReindexOnWriteOfVersionedPrices(): void
    {
        $this->enableFlatPricingStorage();

        $prices = $this->createPrices();
        foreach ($prices as $price) {
            $price->setVersion(self::VERSION);
        }

        $this->clearMessageCollector();
        $this->createWriter()->write($prices);

        $this->assertEmptyMessages(WebsiteSearchReindexTopic::getName());
    }

    private function createWriter(): ProductPriceWriter
    {
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance(new JobInstance());
        $stepExecution = new StepExecution('step', $jobExecution);

        $container = $this->getContainer();

        $writer = new ProductPriceWriter(
            $container->get('doctrine'),
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.context_registry'),
            $container->get('oro_integration.logger.strategy'),
            $container->get('oro_pricing.manager.price_manager'),
            $container->get('oro_platform.optional_listeners.manager')
        );
        // The set of listeners disabled by the oro_pricing.import.writer.product_price service.
        $writer->disableListener('oro_pricing.entity_listener.product_price_cpl');
        $writer->disableListener('oro_pricing.entity_listener.price_list_to_product');
        $writer->disableListener('oro_website.indexation_request_listener');
        $writer->setStepExecution($stepExecution);

        return $writer;
    }

    /**
     * @return ProductPrice[]
     */
    private function createPrices(): array
    {
        $prices = [];
        foreach (['product-3', 'product-4'] as $productReference) {
            foreach ([1, 2] as $quantity) {
                $prices[] = $this->createPrice($productReference, $quantity);
            }
        }

        return $prices;
    }

    private function createPrice(string $productReference = 'product-3', int $quantity = 1): ProductPrice
    {
        $priceList = $this->getReference('price_list_2');
        $product = $this->getReference($productReference);
        $price = new ProductPrice();
        $price->setPrice(Price::create(1, 'USD'));
        $price->setProduct($product);
        $price->setPriceList($priceList);
        $price->setQuantity($quantity);
        $unit = $this->getReference(LoadProductUnits::BOX);
        $price->setUnit($unit);

        return $price;
    }

    private function enableFlatPricingStorage(): void
    {
        $configManager = $this->getGlobalConfigManager();
        $this->savedStorage = $configManager->get('oro_pricing.price_storage');
        $configManager->set('oro_pricing.price_storage', 'flat');
        $configManager->flush();
        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_flat');
    }

    private function restorePricingStorage(): void
    {
        if (null === $this->savedStorage) {
            return;
        }

        $configManager = $this->getGlobalConfigManager();
        $configManager->set('oro_pricing.price_storage', $this->savedStorage);
        $configManager->flush();
        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
        $this->savedStorage = null;
    }

    private function getGlobalConfigManager(): ConfigManager
    {
        return self::getConfigManager('global');
    }
}
