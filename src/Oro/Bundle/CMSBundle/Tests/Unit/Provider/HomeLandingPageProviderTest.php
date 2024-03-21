<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\HomeLandingPageProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HomeLandingPageProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;

    private ManagerRegistry|MockObject $doctrine;

    private HomeLandingPageProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new HomeLandingPageProvider($this->configManager, $this->doctrine);
    }

    public function testGetHomeLandingPageNotFoundInSystemConfiguration(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::HOME_PAGE))
            ->willReturn(null);

        $notFoundPage = (new Page())->setContent(
            '<h2 align="center">No homepage has been set in the system configuration.</h2>'
        );
        self::assertEquals($notFoundPage, $this->provider->getHomeLandingPage());
    }

    public function testGetHomeLandingPageNotFoundInDB(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::HOME_PAGE))
            ->willReturn(1);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($repository);

        $notFoundPage = (new Page())->setContent(
            '<h2 align="center">No homepage has been set in the system configuration.</h2>'
        );
        self::assertEquals($notFoundPage, $this->provider->getHomeLandingPage());
    }

    public function testGetHomeLandingPage(): void
    {
        $page = new Page();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::HOME_PAGE))
            ->willReturn(1);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($page);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($repository);

        self::assertEquals($page, $this->provider->getHomeLandingPage());
    }
}
