<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItemDataProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendRelatedProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class RelatedItemDataProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ConfigManager $configManager;
    private RelatedItemDataProvider $provider;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([LoadFrontendRelatedProductData::class]);
        self::getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $this->configManager = self::getConfigManager();
        $this->provider = self::getContainer()->get('oro_product.tests.related_item.related_product.data_provider');
    }

    protected function tearDown(): void
    {
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MIN_ITEMS),
            Configuration::RELATED_PRODUCTS_MIN_ITEMS_COUNT
        );
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MAX_ITEMS),
            Configuration::RELATED_PRODUCTS_MAX_ITEMS_COUNT
        );
        $this->configManager->flush();

        parent::tearDown();
    }

    public function testRelatedItemsWithDefaultConfigValues()
    {
        // default config minimum 3 and maximum 4
        $data = $this->provider->getRelatedItems($this->getReference('product30'));
        self::assertEquals([], $data);
    }

    public function testRelatedItemsWithMinimumCustomConfigValues()
    {
        // default config minimum 1 and maximum 1
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MIN_ITEMS),
            1
        );
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MAX_ITEMS),
            1
        );
        $this->configManager->flush();
        $data = $this->provider->getRelatedItems($this->getReference('product10'));
        self::assertCount(1, $data);
    }

    public function testRelatedItemsWithCustomConfigValuesGreaterThanDefault()
    {
        // default config minimum 4 and maximum 15
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MIN_ITEMS),
            4
        );
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MAX_ITEMS),
            15
        );
        $this->configManager->flush();
        $data = $this->provider->getRelatedItems($this->getReference('product10'));
        self::assertCount(15, $data);
    }
}
