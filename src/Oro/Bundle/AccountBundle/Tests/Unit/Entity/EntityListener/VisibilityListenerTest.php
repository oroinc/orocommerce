<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\AccountBundle\Async\Topics;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageHandler;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Entity\EntityListener\VisibilityListener;
use Oro\Component\Testing\Unit\EntityTrait;

class VisibilityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    
    /**
     * @var VisibilityMessageHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $visibilityChangeMessageHandler;

    /**
     * @var VisibilityListener
     */
    protected $visibilityListener;

    protected function setUp()
    {
        $this->visibilityChangeMessageHandler = $this->getMockBuilder(VisibilityMessageHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->visibilityListener = new VisibilityListener(
            $this->visibilityChangeMessageHandler,
            Topics::RESOLVE_PRODUCT_VISIBILITY
        );
    }

    public function testPostPersist()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with(Topics::RESOLVE_PRODUCT_VISIBILITY, $visibility);
        $this->visibilityListener->postPersist($visibility);
    }

    public function testPreUpdate()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with(Topics::RESOLVE_PRODUCT_VISIBILITY, $visibility);

        $this->visibilityListener->preUpdate($visibility);
    }

    public function testPreRemove()
    {
        /** @var VisibilityInterface|\PHPUnit_Framework_MockObject_MockObject $visibility **/
        $visibility = $this->getMock(VisibilityInterface::class);

        $this->visibilityChangeMessageHandler->expects($this->once())
            ->method('addVisibilityMessageToSchedule')
            ->with(Topics::RESOLVE_PRODUCT_VISIBILITY, $visibility);

        $this->visibilityListener->preRemove($visibility);
    }
}
