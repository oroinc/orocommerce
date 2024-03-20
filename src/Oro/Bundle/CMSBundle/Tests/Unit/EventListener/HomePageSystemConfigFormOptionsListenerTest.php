<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\EventListener\HomePageSystemConfigFormOptionsListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HomePageSystemConfigFormOptionsListenerTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private HomePageSystemConfigFormOptionsListener $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new HomePageSystemConfigFormOptionsListener();
    }

    /**
     * @dataProvider dataProviderOnFormOptions
     */
    public function testOnFormOptions(
        array $allFormOptions,
        bool $hasWebCatalog,
        array $expected,
        string $scopeEntityName
    ): void {
        $this->configManager->expects(self::any())
            ->method('get')
            ->with(WebCatalogUsageProvider::SETTINGS_KEY)
            ->willReturn($hasWebCatalog);

        $this->configManager->expects(self::any())
            ->method('getScopeEntityName')
            ->willReturn($scopeEntityName);

        $event = new ConfigSettingsFormOptionsEvent($this->configManager, $allFormOptions);

        $this->listener->onFormOptions($event);

        self::assertEquals($expected, $event->getAllFormOptions());
    }

    public function dataProviderOnFormOptions(): array
    {
        return [
            'without oro_cms.home_page' => [
                'allFormOptions' => [],
                'hasWebCatalog' => true,
                'expected' => [],
                'scopeEntityName' => 'website'
            ],
            'without oro_web_catalog.web_catalog' => [
                'allFormOptions' => ['oro_cms.home_page' => []],
                'hasWebCatalog' => false,
                'expected' => ['oro_cms.home_page' => []],
                'scopeEntityName' => 'website'
            ],
            'with oro_web_catalog.web_catalog' => [
                'allFormOptions' => ['oro_cms.home_page' => []],
                'hasWebCatalog' => true,
                'expected' => [],
                'scopeEntityName' => 'website'
            ],
            'application level' => [
                'allFormOptions' => ['oro_cms.home_page' => []],
                'hasWebCatalog' => false,
                'expected' => [
                    'oro_cms.home_page' => [
                        'resettable' => false
                    ]
                ],
                'scopeEntityName' => GlobalScopeManager::SCOPE_NAME
            ],
        ];
    }
}
