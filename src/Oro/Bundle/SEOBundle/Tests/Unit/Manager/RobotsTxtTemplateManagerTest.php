<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtDistTemplateManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtTemplateManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RobotsTxtTemplateManagerTest extends TestCase
{
    private const TEMPLATE_KEY = 'oro_seo.sitemap_robots_txt_template';

    private ConfigManager|MockObject $configManager;

    private RobotsTxtDistTemplateManager|MockObject $robotsTxtDistTemplateManager;

    private RobotsTxtTemplateManager $robotsTxtTemplateManager;

    protected function setUp(): void
    {
        $this->robotsTxtDistTemplateManager = $this->createMock(RobotsTxtDistTemplateManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->robotsTxtTemplateManager = new RobotsTxtTemplateManager(
            $this->robotsTxtDistTemplateManager,
            $this->configManager
        );
    }

    /**
     * @dataProvider getTemplateContentWithSavedSystemConfigValueDataProvider
     */
    public function testGetTemplateContentWithSavedSystemConfigValue(
        ?string $savedConfigValue,
        ?WebsiteInterface $website,
        string $expectedValue
    ): void {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::TEMPLATE_KEY)
            ->willReturn($savedConfigValue);

        $this->robotsTxtDistTemplateManager->expects(self::never())
            ->method('getDistTemplateContent');

        self::assertEquals(
            $expectedValue,
            $this->robotsTxtTemplateManager->getTemplateContent($website)
        );
    }

    public function getTemplateContentWithSavedSystemConfigValueDataProvider(): array
    {
        return [
            [
                'savedConfigValue' => null,
                'website' => null,
                'expectedValue' => '',
            ],
            [
                'savedConfigValue' => null,
                'website' => new Website(),
                'expectedValue' => '',
            ],
            [
                'savedConfigValue' => '#test robots file\n"',
                'website' => null,
                'expectedValue' => '#test robots file\n"',
            ],
            [
                'savedConfigValue' => '#test robots file\n"',
                'website' => new Website(),
                'expectedValue' => '#test robots file\n"',
            ],
        ];
    }

    /**
     * @dataProvider getTemplateContentWithDefaultTemplateDataProvider
     */
    public function testGetTemplateContentWithDefaultTemplate(?WebsiteInterface $website): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::TEMPLATE_KEY)
            ->willReturn('');

        $defaultDistTemplate = '#test robots file\n"';
        $this->robotsTxtDistTemplateManager->expects(self::once())
            ->method('getDistTemplateContent')
            ->with($website)
            ->willReturn($defaultDistTemplate);

        self::assertEquals(
            $defaultDistTemplate,
            $this->robotsTxtTemplateManager->getTemplateContent($website)
        );
    }

    public function getTemplateContentWithDefaultTemplateDataProvider(): array
    {
        return [
            [null],
            [new Website()],
        ];
    }
}
