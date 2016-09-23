<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\VisibilityBundle\Entity\EntityListener\ProductVisibilityListener;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Component\Testing\Unit\EntityTrait;

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

    protected function setUp()
    {
        $this->visibilityChangeMessageHandler = $this->getMockBuilder(VisibilityMessageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->visibilityListener = new ProductVisibilityListener($this->visibilityChangeMessageHandler);

        $this->visibilityListener->setTopic('oro_account.visibility.resolve_product_visibility');
    }

    public function testPostPersist()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with('oro_account.visibility.resolve_product_visibility', $visibility);
        $this->visibilityListener->postPersist($visibility);
    }

    public function testPreUpdate()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with('oro_account.visibility.resolve_product_visibility', $visibility);

        $this->visibilityListener->preUpdate($visibility);
    }

    public function testPreRemove()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with('oro_account.visibility.resolve_product_visibility', $visibility);

        $this->visibilityListener->preRemove($visibility);
    }
}
