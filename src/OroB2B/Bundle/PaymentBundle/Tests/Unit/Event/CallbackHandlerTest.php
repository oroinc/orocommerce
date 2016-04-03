<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\PaymentBundle\Event\CallbackHandler;
use Symfony\Component\HttpFoundation\Response;

class CallbackHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CallbackHandler */
    protected $handler;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new CallbackHandler(
            $this->eventDispatcher,
            $this->doctrineHelper,
            'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction'
        );
    }

    public function testHandleNoEntity()
    {
        $response = new Response();
        $event = new CallbackReturnEvent();

        $this->doctrineHelper->expects($this->once())->method('getEntity')->willReturn(null);
        $this->doctrineHelper->expects($this->never())->method('getEntityManager');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $result = $this->handler->handle(1, $event);
        $this->assertEquals($response, $result);
        $this->assertNotSame($response, $result);
    }

    public function testHandle()
    {
        $response = new Response();
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setAction('type');

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntity')->willReturn($transaction);
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($objectManager);
        $objectManager->expects($this->once())->method('transactional')
            ->with(
                $this->callback(
                    function (\Closure $closure) use ($objectManager) {
                        $closure($objectManager);

                        return true;
                    }
                )
            );
        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->withConsecutive(
                [CallbackReturnEvent::NAME, $event],
                [CallbackReturnEvent::NAME . '.type', $event]
            )->willReturnCallback(
                function ($name, CallbackReturnEvent $event) use ($response) {
                    $event->setResponse($response);
                }
            );

        $result = $this->handler->handle(1, $event);
        $this->assertEquals($response, $result);
        $this->assertSame($response, $result);
    }

    public function testHandleWithException()
    {
        $response = new Response();
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setAction('type');

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntity')->willReturn($transaction);
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($objectManager);

        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('error')->with('message');
        $this->handler->setLogger($logger);
        $objectManager->expects($this->once())->method('transactional')
            ->with(
                $this->callback(
                    function (\Closure $closure) use ($objectManager) {
                        $closure($objectManager);

                        return true;
                    }
                )
            )
            ->willThrowException(new \Exception('message'));

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->withConsecutive(
                [CallbackReturnEvent::NAME, $event],
                [CallbackReturnEvent::NAME . '.type', $event]
            )->willReturnCallback(
                function ($name, CallbackReturnEvent $event) use ($response) {
                    $event->setResponse($response);
                }
            );

        $result = $this->handler->handle(1, $event);
        $this->assertEquals($response, $result);
        $this->assertSame($response, $result);
    }
}
