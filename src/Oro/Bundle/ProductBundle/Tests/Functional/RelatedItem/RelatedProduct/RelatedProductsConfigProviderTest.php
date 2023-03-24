<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\RelatedItem\RelatedProduct;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RelatedProductsConfigProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ConfigManager $configManager;
    private RelatedProductsConfigProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configManager = self::getConfigManager();
        $this->provider = self::getContainer()->get('oro_product.tests.related_item.related_product.config_provider');
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

    public function testGetMinimumItems()
    {
        // Default value
        self::assertEquals(Configuration::RELATED_PRODUCTS_MIN_ITEMS_COUNT, $this->provider->getMinimumItems());
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MIN_ITEMS),
            15
        );
        $this->configManager->flush();
        self::assertEquals(15, $this->provider->getMinimumItems());
    }

    public function testGetMaximumItems()
    {
        // Default value
        self::assertEquals(Configuration::RELATED_PRODUCTS_MAX_ITEMS_COUNT, $this->provider->getMaximumItems());
        $this->configManager->set(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_MAX_ITEMS),
            15
        );
        $this->configManager->flush();
        self::assertEquals(15, $this->provider->getMaximumItems());
    }
}
