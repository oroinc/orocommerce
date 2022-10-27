<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\FrontendEmulator;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class FrontendEmulatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $currentLocalizationProvider;

    /** @var UserLocalizationManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userLocalizationManager;

    /** @var FrontendEmulator */
    private $frontendEmulator;

    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);
        $this->userLocalizationManager = $this->createMock(UserLocalizationManagerInterface::class);

        $this->frontendEmulator = new FrontendEmulator(
            $this->websiteManager,
            $this->currentLocalizationProvider,
            $this->userLocalizationManager
        );
    }

    public function testStartFrontendRequestEmulation(): void
    {
        $website = $this->createMock(Website::class);
        $localization = $this->createMock(Localization::class);

        $this->websiteManager->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($website);
        $this->websiteManager->expects(self::once())
            ->method('setCurrentWebsite')
            ->with(self::identicalTo($website));

        $this->userLocalizationManager->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);
        $this->currentLocalizationProvider->expects(self::once())
            ->method('setCurrentLocalization')
            ->with(self::identicalTo($localization));

        $this->frontendEmulator->startFrontendRequestEmulation();
    }

    public function testStopFrontendRequestEmulation(): void
    {
        $this->websiteManager->expects(self::once())
            ->method('setCurrentWebsite')
            ->with(self::isNull());

        $this->currentLocalizationProvider->expects(self::once())
            ->method('setCurrentLocalization')
            ->with(self::isNull());

        $this->frontendEmulator->stopFrontendRequestEmulation();
    }
}
