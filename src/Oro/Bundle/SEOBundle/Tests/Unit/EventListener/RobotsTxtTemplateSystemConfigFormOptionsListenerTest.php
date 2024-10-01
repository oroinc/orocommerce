<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\SEOBundle\EventListener\RobotsTxtTemplateSystemConfigFormOptionsListener;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtDistTemplateManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RobotsTxtTemplateSystemConfigFormOptionsListenerTest extends TestCase
{
    private const SETTINGS_KEY = 'oro_seo___sitemap_robots_txt_template';

    private EntityRepository|MockObject $websiteRepository;

    private RobotsTxtDistTemplateManager|MockObject $robotsTxtDistTemplateManager;

    private RobotsTxtTemplateSystemConfigFormOptionsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteRepository = $this->createMock(EntityRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->websiteRepository);

        $this->robotsTxtDistTemplateManager = $this->createMock(RobotsTxtDistTemplateManager::class);

        $this->listener = new RobotsTxtTemplateSystemConfigFormOptionsListener(
            $doctrine,
            $this->robotsTxtDistTemplateManager
        );
    }

    public function testOnFormPreSetDataNoConfigKey(): void
    {
        $this->websiteRepository->expects(self::never())
            ->method('find');

        $this->robotsTxtDistTemplateManager->expects(self::never())
            ->method('getDistTemplateContent');

        $settings = ['another_key' => ['value' => 'bar']];
        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            $settings
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals($settings, $event->getSettings());
    }

    /**
     * @dataProvider getOnFormPreSetDataTemplateAlreadySavedDataProvider
     */
    public function testOnFormPreSetDataTemplateAlreadySaved(?string $savedContent): void
    {
        $this->websiteRepository->expects(self::never())
            ->method('find');

        $this->robotsTxtDistTemplateManager->expects(self::never())
            ->method('getDistTemplateContent');

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::SETTINGS_KEY => ['value' => $savedContent]]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $savedContent]], $event->getSettings());
    }

    public function getOnFormPreSetDataTemplateAlreadySavedDataProvider(): array
    {
        return [
            'saved empty text area' => [null],
            'saved text area value' => ['#test robots file\n"'],
        ];
    }

    /**
     * @dataProvider getOnFormPreSetDataSetDefaultValueDataProvider
     */
    public function testOnFormPreSetDataSetDefaultValue(string $scope): void
    {
        $this->websiteRepository->expects(self::never())
            ->method('find');

        $defaultContent = '#test robots file\n"';
        $this->robotsTxtDistTemplateManager->expects(self::once())
            ->method('getDistTemplateContent')
            ->with(null)
            ->willReturn($defaultContent);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::once())
            ->method('getScopeEntityName')
            ->willReturn($scope);

        $event = new ConfigSettingsUpdateEvent(
            $configManager,
            [self::SETTINGS_KEY => ['value' => '']]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $defaultContent]], $event->getSettings());
    }

    public function getOnFormPreSetDataSetDefaultValueDataProvider(): array
    {
        return [
            ['global'],
            ['organization'],
        ];
    }

    public function testOnFormPreSetDataSetDefaultValueWebsiteScope(): void
    {
        $scopeId = 1;
        $website = new Website();
        $this->websiteRepository->expects(self::once())
            ->method('find')
            ->with($scopeId)
            ->willReturn($website);

        $defaultContent = '#test robots file\n"';
        $this->robotsTxtDistTemplateManager->expects(self::once())
            ->method('getDistTemplateContent')
            ->with($website)
            ->willReturn($defaultContent);
        $this->robotsTxtDistTemplateManager->expects(self::once())
            ->method('isDistTemplateFileExist')
            ->with($website)
            ->willReturn(false);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::once())
            ->method('getScopeEntityName')
            ->willReturn('website');
        $configManager->expects(self::once())
            ->method('getScopeId')
            ->willReturn($scopeId);

        $event = new ConfigSettingsUpdateEvent(
            $configManager,
            [self::SETTINGS_KEY => ['value' => '']]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $defaultContent]], $event->getSettings());
    }

    public function testOnFormPreSetDataSetDefaultValueAndUseParentValueWebsiteScope(): void
    {
        $scopeId = 1;
        $website = new Website();
        $this->websiteRepository->expects(self::once())
            ->method('find')
            ->with($scopeId)
            ->willReturn($website);

        $defaultDomainContent = '#test domain robots file\n"';
        $this->robotsTxtDistTemplateManager->expects(self::once())
            ->method('getDistTemplateContent')
            ->with($website)
            ->willReturn($defaultDomainContent);
        $this->robotsTxtDistTemplateManager->expects(self::once())
            ->method('isDistTemplateFileExist')
            ->with($website)
            ->willReturn(true);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::once())
            ->method('getScopeEntityName')
            ->willReturn('website');
        $configManager->expects(self::once())
            ->method('getScopeId')
            ->willReturn($scopeId);

        $event = new ConfigSettingsUpdateEvent(
            $configManager,
            [self::SETTINGS_KEY => ['value' => '']]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals(
            [
                self::SETTINGS_KEY => [
                    ConfigManager::VALUE_KEY => $defaultDomainContent,
                    ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => false,
                ],
            ],
            $event->getSettings()
        );
    }
}
