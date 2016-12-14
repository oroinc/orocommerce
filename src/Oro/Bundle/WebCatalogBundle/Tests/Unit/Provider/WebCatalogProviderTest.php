<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;

class WebCatalogProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $webCatalogRepository;

    /**
     * @var WebCatalogProvider
     */
    protected $webCatalogProvider;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->webCatalogRepository = $this->getMockBuilder(EntityRepository::class)
            ->setMethods(['findOneById'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->webCatalogProvider = new WebCatalogProvider($this->config, $this->webCatalogRepository);
    }

    public function testGetWebCatalog()
    {
        $this->config
            ->method('get')
            ->with('oro_web_catalog.web_catalog')
            ->willReturn(308);
        
        $this->webCatalogRepository
            ->method('findOneById')
            ->with(308)
            ->willReturn($this->getMock(WebCatalog::class));

        $webCatalog = $this->webCatalogProvider->getWebCatalog();

        $this->assertInstanceOf(WebCatalog::class, $webCatalog);
    }
    
    public function testGetWebCatalogWhenNoWebCatalog()
    {
        $this->config
            ->method('get')
            ->with('oro_web_catalog.web_catalog')
            ->willReturn(null);
        
        $expectedNull = $this->webCatalogProvider->getWebCatalog();
        
        $this->assertNull($expectedNull);
    }
}
