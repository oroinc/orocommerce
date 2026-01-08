<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\GuestAccess\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\GuestAccess\Provider\CmsPagesGuestAccessAllowedUrlsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class CmsPagesGuestAccessAllowedUrlsProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private RouterInterface&MockObject $router;
    private ManagerRegistry&MockObject $doctrine;
    private ObjectRepository&MockObject $pageRepository;
    private CmsPagesGuestAccessAllowedUrlsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->pageRepository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($this->pageRepository);

        $this->provider = new CmsPagesGuestAccessAllowedUrlsProvider(
            $this->configManager,
            $this->router,
            $this->doctrine
        );
    }

    public function testGetAllowedUrlsPatternsReturnsEmptyArrayWhenNoCmsPagesConfigured(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::GUEST_ACCESS_ALLOWED_CMS_PAGES))
            ->willReturn([]);

        $patterns = $this->provider->getAllowedUrlsPatterns();
        self::assertSame([], $patterns);
    }

    public function testGetAllowedUrlsPatternsWithCmsPages(): void
    {
        $page = $this->createMock(Page::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::GUEST_ACCESS_ALLOWED_CMS_PAGES))
            ->willReturn([42]);

        $this->pageRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($page);

        $this->router->expects(self::once())
            ->method('generate')
            ->with('oro_cms_frontend_page_view', ['id' => 42], RouterInterface::ABSOLUTE_PATH)
            ->willReturn('/cms/page/view/42');

        $patterns = $this->provider->getAllowedUrlsPatterns();
        self::assertCount(1, $patterns);
        self::assertSame('^/cms/page/view/42$', $patterns[0]);
    }

    public function testGetAllowedUrlsPatternsSkipsNonExistentPage(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::GUEST_ACCESS_ALLOWED_CMS_PAGES))
            ->willReturn([999]);

        $this->pageRepository->expects(self::once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        self::assertSame([], $this->provider->getAllowedUrlsPatterns());
    }

    public function testGetAllowedUrlsPatternsWithMultipleCmsPages(): void
    {
        $page1 = $this->createMock(Page::class);
        $page2 = $this->createMock(Page::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::GUEST_ACCESS_ALLOWED_CMS_PAGES))
            ->willReturn([42, 43]);

        $this->pageRepository->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [42, $page1],
                [43, $page2],
            ]);

        $this->router->expects(self::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [
                    'oro_cms_frontend_page_view',
                    ['id' => 42],
                    RouterInterface::ABSOLUTE_PATH,
                    '/cms/page/view/42'
                ],
                [
                    'oro_cms_frontend_page_view',
                    ['id' => 43],
                    RouterInterface::ABSOLUTE_PATH,
                    '/cms/page/view/43'
                ],
            ]);

        $patterns = $this->provider->getAllowedUrlsPatterns();
        self::assertCount(2, $patterns);
        self::assertSame('^/cms/page/view/42$', $patterns[0]);
        self::assertSame('^/cms/page/view/43$', $patterns[1]);
    }
}
