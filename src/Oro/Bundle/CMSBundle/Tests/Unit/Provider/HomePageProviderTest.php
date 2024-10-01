<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\HomePageProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HomePageProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private ManagerRegistry|MockObject $doctrine;
    private HomePageProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new HomePageProvider($this->configManager, $this->doctrine);
    }

    private function getNotFoundPage(): Page
    {
        $page = new Page();
        $page->setContent('<h2 align="center">No homepage has been set in the system configuration.</h2>');

        return $page;
    }

    public function testGetHomePageNotFoundInSystemConfiguration(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_cms.home_page')
            ->willReturn(null);

        self::assertEquals($this->getNotFoundPage(), $this->provider->getHomePage());
    }

    public function testGetHomePageNotFoundInDB(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_cms.home_page')
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Page::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Page::class, 1)
            ->willReturn(null);

        self::assertEquals($this->getNotFoundPage(), $this->provider->getHomePage());
    }

    public function testGetHomePage(): void
    {
        $page = new Page();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_cms.home_page')
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Page::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Page::class, 1)
            ->willReturn($page);

        self::assertEquals($page, $this->provider->getHomePage());
    }
}
