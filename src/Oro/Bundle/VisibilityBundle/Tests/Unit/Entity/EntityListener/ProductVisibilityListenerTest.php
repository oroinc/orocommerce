<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\VisibilityBundle\Entity\EntityListener\ProductVisibilityListener;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class ProductVisibilityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    
    /**
     * @var VisibilityMessageHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $visibilityChangeMessageHandler;

    /**
     * @var ProductVisibilityListener
     */
    protected $visibilityListener;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->visibilityChangeMessageHandler = $this->getMockBuilder(VisibilityMessageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->visibilityListener = new ProductVisibilityListener($this->visibilityChangeMessageHandler, $this->scopeManager);

        $this->visibilityListener->setTopic('oro_visibility.visibility.resolve_product_visibility');
    }

    public function testPostPersist()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with('oro_visibility.visibility.resolve_product_visibility', $visibility);
        $this->visibilityListener->postPersist($visibility);
    }

    public function testPreUpdate()
    {
        $this->markTestSkipped('Should be fixed after BB-4710');
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with('oro_visibility.visibility.resolve_product_visibility', $visibility);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with('product_visibility', $visibility);

        $this->visibilityListener->preUpdate($visibility);
    }

    public function testPreRemove()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with('oro_visibility.visibility.resolve_product_visibility', $visibility);

        $this->visibilityListener->preRemove($visibility);
    }
}
