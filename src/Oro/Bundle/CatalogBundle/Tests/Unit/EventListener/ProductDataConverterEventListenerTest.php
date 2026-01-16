<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductDataConverterEventListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
class ProductDataConverterEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private ProductDataConverterEventListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductDataConverterEventListener();
    }

    public function testOnBackendHeaderWithoutConfigManager()
    {
        $data = ['sku', 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals(
            ['sku', 'name', AbstractProductImportEventListener::CATEGORY_KEY],
            $event->getData()
        );
    }

    public function testOnBackendHeaderDoesNotAddDuplicateCategoryKey()
    {
        $data = ['sku', AbstractProductImportEventListener::CATEGORY_KEY, 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals($data, $event->getData());
    }

    public function testOnBackendHeaderWithConfigManagerAndDefaultTitleEnabled()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE),
                    false,
                    false,
                    null,
                    true
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    false
                ],
            ]);

        $this->listener->setConfigManager($configManager);

        $data = ['sku', 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals(
            ['sku', 'name', AbstractProductImportEventListener::CATEGORY_KEY],
            $event->getData()
        );
    }

    public function testOnBackendHeaderWithConfigManagerAndCategoryPathEnabled()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE),
                    false,
                    false,
                    null,
                    false
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    true
                ],
            ]);

        $this->listener->setConfigManager($configManager);

        $data = ['sku', 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals(
            ['sku', 'name', AbstractProductImportEventListener::CATEGORY_PATH_KEY],
            $event->getData()
        );
    }

    public function testOnBackendHeaderWithConfigManagerAndBothOptionsEnabled()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE),
                    false,
                    false,
                    null,
                    true
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    true
                ],
            ]);

        $this->listener->setConfigManager($configManager);

        $data = ['sku', 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals(
            [
                'sku',
                'name',
                AbstractProductImportEventListener::CATEGORY_KEY,
                AbstractProductImportEventListener::CATEGORY_PATH_KEY
            ],
            $event->getData()
        );
    }

    public function testOnBackendHeaderWithConfigManagerAndBothOptionsDisabled()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE),
                    false,
                    false,
                    null,
                    false
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    false
                ],
            ]);

        $this->listener->setConfigManager($configManager);

        $data = ['sku', 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals(['sku', 'name'], $event->getData());
    }

    public function testOnBackendHeaderDoesNotAddDuplicateCategoryPath()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE),
                    false,
                    false,
                    null,
                    false
                ],
                [Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH), false, false, null, true],
            ]);

        $this->listener->setConfigManager($configManager);

        $data = ['sku', AbstractProductImportEventListener::CATEGORY_PATH_KEY, 'name'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals($data, $event->getData());
    }

    public function testOnBackendHeaderDoesNotAddDuplicatesWhenBothEnabled()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE),
                    false,
                    false,
                    null,
                    true
                ],
                [Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH), false, false, null, true],
            ]);

        $this->listener->setConfigManager($configManager);

        $data = [
            'sku',
            AbstractProductImportEventListener::CATEGORY_KEY,
            'name',
            AbstractProductImportEventListener::CATEGORY_PATH_KEY
        ];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);

        $this->assertEquals($data, $event->getData());
    }
}
