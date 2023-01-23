<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceResetStrategy;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceResetStrategyTest extends WebTestCase
{
    use ProductPriceReference;

    private ShardManager $shardManager;
    private ProductPriceResetStrategy $strategy;
    private StepExecutionProxyContext $context;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductPrices::class]);

        $container = $this->getContainer();

        $this->strategy = new ProductPriceResetStrategy(
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

        $this->context = new StepExecutionProxyContext(new StepExecution('step', new JobExecution()));
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(ProductPrice::class);
        $this->strategy->setOwnershipSetter($container->get('oro_organization.entity_ownership_associations_setter'));
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
    }

    public function testProcessResetPriceListProductPrices()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productPrice = $this->getPriceByReference('product_price.1');

        $actualPrices = $this->getContainer()->get('doctrine')
            ->getRepository(ProductPrice::class)
            ->findByPriceList($this->shardManager, $priceList, ['priceList' => $priceList->getId()]);
        $this->assertCount(8, $actualPrices);

        $expectedPricesIds = [
            $productPrice->getId(),
            $this->getPriceByReference('product_price.2')->getId(),
            $this->getPriceByReference('product_price.3')->getId(),
        ];

        $actualPricesIds = array_map(
            function (ProductPrice $price) {
                return $price->getId();
            },
            $actualPrices
        );

        foreach ($expectedPricesIds as $price) {
            $this->assertContains($price, $actualPricesIds);
        }

        $body['process'] = ProcessorRegistry::TYPE_IMPORT;
        $body['processorAlias'] = 'oro_pricing_product_price.reset';
        $body['options']['price_list_id'] = $priceList->getId();

        $this->getContainer()->get('event_dispatcher')->dispatch(
            new BeforeImportChunksEvent($body),
            Events::BEFORE_CREATING_IMPORT_CHUNK_JOBS
        );

        $this->assertEquals([], $this->context->getErrors());

        // ProductPriceResetStrategy works in conjunction with PreChunksMessageProcessor
        // which clears the price list before importing prices in chunks
        $this->assertEmpty(
            $this->getContainer()->get('doctrine')
                ->getRepository(ProductPrice::class)
                ->findByPriceList($this->shardManager, $priceList, ['priceList' => $priceList->getId()])
        );

        $this->strategy->process($productPrice);

        $this->assertNotEmpty($productPrice->getPrice());
        $this->assertEquals('USD', $productPrice->getPrice()->getCurrency());
        $this->assertEquals(12.2, $productPrice->getPrice()->getValue());
        $this->assertNotEmpty($productPrice->getProduct());
    }
}
