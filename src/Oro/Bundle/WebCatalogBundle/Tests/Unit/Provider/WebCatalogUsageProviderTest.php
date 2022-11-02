<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

class WebCatalogUsageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var WebsiteRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var WebCatalogUsageProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->repository = $this->createMock(WebsiteRepository::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);

        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($entityManager);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->repository);

        $this->provider = new WebCatalogUsageProvider($this->configManager, $doctrine);
    }

    /**
     * @dataProvider inUseDataProvider
     * @param WebCatalogInterface $webCatalog
     * @param int|null $configuredCatalogId
     * @param bool $isInUse
     */
    public function testIsInUse(WebCatalogInterface $webCatalog, $configuredCatalogId, $isInUse)
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(WebCatalogUsageProvider::SETTINGS_KEY)
            ->willReturn($configuredCatalogId);

        $this->assertEquals($isInUse, $this->provider->isInUse($webCatalog));
    }

    public function testNoWebCatalogsAssigned()
    {
        $this->assertEquals([], $this->provider->getAssignedWebCatalogs());
    }

    /**
     * @dataProvider getAssignedWebCatalogsDataProvider
     * @param int|null $configuredCatalogId
     */
    public function testGetAssignedWebCatalogs($configuredCatalogId)
    {
        $website = $this->createMock(Website::class);
        $website->expects(self::any())
            ->method('getId')
            ->willReturn(123);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(WebCatalogUsageProvider::SETTINGS_KEY)
            ->willReturn($configuredCatalogId);

        $this->repository->expects(self::any())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->assertEquals([123 => $configuredCatalogId], $this->provider->getAssignedWebCatalogs());
    }

    /**
     * @return array
     */
    public function inUseDataProvider()
    {
        return [
            'used value returned' => [
                $this->getWebCatalog(1),
                1,
                true
            ],
            'not used value returned' => [
                $this->getWebCatalog(1),
                2,
                false
            ],
            'default' => [
                $this->getWebCatalog(1),
                null,
                false
            ]
        ];
    }

    /**
     * @return array
     */
    public function getAssignedWebCatalogsDataProvider()
    {
        return [
            [2], [1]
        ];
    }

    /**
     * @return WebCatalogInterface
     */
    private function getWebCatalog($id)
    {
        $webCatalog = $this->createMock(WebCatalogInterface::class);
        $webCatalog->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $webCatalog;
    }
}
