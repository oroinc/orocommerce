<?php

namespace OroB2B\Bundle\WebsiteConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\WebsiteConfigBundle\Config\WebsiteScopeManager;

class WebsiteScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject $websiteManager
     **/
    protected $websiteManager;

    /**
     * @var WebsiteScopeManager
     */
    protected $websiteScopeManager;

    protected function setUp()
    {
        $this->doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->cache = $this->getMock('Doctrine\Common\Cache\CacheProvider');
        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteScopeManager = new WebsiteScopeManager($this->doctrine, $this->cache);
        $this->websiteScopeManager->setWebsiteManager($this->websiteManager);
    }

    public function testGetScopedEntityName()
    {
        $this->assertEquals('website', $this->websiteScopeManager->getScopedEntityName());
    }

    public function testGetScopeId()
    {
        $this->assertEquals(0, $this->websiteScopeManager->getScopeId());
    }

    public function testGetScopeIdWithoutWebsite()
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite');
        $this->assertEquals(0, $this->websiteScopeManager->getScopeId());
    }

    public function testGetScopeIdWithWebsite()
    {
        $scopeId = 42;

        $website = $this->getEntity(Website::class, ['id' => $scopeId]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->assertEquals($scopeId, $this->websiteScopeManager->getScopeId());
    }

    public function testSetScopeId()
    {
        $scopeId = 2;
        $this->websiteScopeManager->setScopeId($scopeId);
        $this->websiteManager->expects($this->never())
            ->method($this->anything());
        $this->assertEquals(2, $this->websiteScopeManager->getScopeId());
    }
}
