<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackHandler;

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

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $this->doctrineHelper->expects($this->never())->method('getEntityManager');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $result = $this->handler->handle('id', 'token', $event);
        $this->assertEquals($response, $result);
        $this->assertNotSame($response, $result);
    }

    public function testHandle()
    {
        $response = new Response();
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setPaymentMethod('paymentMethod');

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())->method('findOneBy')->willReturn($transaction);
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($objectManager);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

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
                [CallbackReturnEvent::NAME . '.paymentMethod', $event]
            )->willReturnCallback(
                function ($name, CallbackReturnEvent $event) use ($response) {
                    $event->setResponse($response);
                }
            );

        $result = $this->handler->handle('id', 'token', $event);
        $this->assertEquals($response, $result);
        $this->assertSame($response, $result);
    }

    public function testHandleWithException()
    {
        $response = new Response();
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setPaymentMethod('paymentMethod');

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())->method('findOneBy')->willReturn($transaction);
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($objectManager);
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
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
                [CallbackReturnEvent::NAME . '.paymentMethod', $event]
            )->willReturnCallback(
                function ($name, CallbackReturnEvent $event) use ($response) {
                    $event->setResponse($response);
                }
            );

        $result = $this->handler->handle('id', 'token', $event);
        $this->assertEquals($response, $result);
        $this->assertSame($response, $result);
    }
}
