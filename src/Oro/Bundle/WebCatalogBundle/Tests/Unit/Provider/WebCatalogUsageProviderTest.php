<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class WebCatalogUsageProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var WebCatalogUsageProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new WebCatalogUsageProvider($this->configManager);
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

        $this->assertEquals([0 => $configuredCatalogId], $this->provider->getAssignedWebCatalogs());
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
            [2], [null], [false]
        ];
    }
}
