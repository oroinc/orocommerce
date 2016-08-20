<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class WebsiteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var WebsiteManager
     */
    protected $manager;

    public function setUp()
    {
        $this->managerRegistry = $this->getMock(ManagerRegistry::class);

        $this->manager = new WebsiteManager($this->managerRegistry);
    }

    public function tearDown()
    {
        unset($this->managerRegistry, $this->manager);
    }

    public function testGetCurrentWebsite()
    {
        $repository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $website = new Website();
        $repository->expects($this->once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $objectManager = $this->getMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repository);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($objectManager);

        $this->assertSame($website, $this->manager->getCurrentWebsite());
    }
}
