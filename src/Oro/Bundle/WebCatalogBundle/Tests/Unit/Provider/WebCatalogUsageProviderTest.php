<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class WebCatalogUsageProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var WebCatalogUsageProvider
     */
    private $provider;

    /**
     * @var WebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($entityManager);

        $entityManager->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->repository);

        $this->provider = new WebCatalogUsageProvider($this->configManager, $doctrine);
    }

    /**
     * @dataProvider inUseDataProvider
     * @param WebCatalog $webCatalog
     * @param int|null $configuredCatalogId
     * @param bool $isInUse
     */
    public function testIsInUse(WebCatalog $webCatalog, $configuredCatalogId, $isInUse)
    {
        $this->configManager->expects($this->once())
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
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(WebCatalogUsageProvider::SETTINGS_KEY)
            ->willReturn($configuredCatalogId);

        $this->repository->expects($this->any())
            ->method('getDefaultWebsite')
            ->willReturn($this->getEntity(Website::class, ['id' => 1]));

        $this->assertEquals([1 => $configuredCatalogId], $this->provider->getAssignedWebCatalogs());
    }

    /**
     * @return array
     */
    public function inUseDataProvider()
    {
        return [
            'used int value returned' => [
                $this->getEntity(WebCatalog::class, ['id' => 1]),
                1,
                true
            ],
            'used string value returned' => [
                $this->getEntity(WebCatalog::class, ['id' => 1]),
                '1',
                true
            ],
            'not used int value returned' => [
                $this->getEntity(WebCatalog::class, ['id' => 1]),
                2,
                false
            ],
            'not used string value returned' => [
                $this->getEntity(WebCatalog::class, ['id' => 1]),
                '2',
                false
            ],
            'default' => [
                $this->getEntity(WebCatalog::class, ['id' => 1]),
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
}
