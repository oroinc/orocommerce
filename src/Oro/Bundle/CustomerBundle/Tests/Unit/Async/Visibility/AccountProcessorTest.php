<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Async\Visibility;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\CustomerBundle\Async\Visibility\AccountProcessor;
use Oro\Bundle\CustomerBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\CustomerBundle\Model\MessageFactoryInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

use Psr\Log\LoggerInterface;

class AccountProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var AccountPartialUpdateDriverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $driver;

    /**
     * @var AccountProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $this->messageFactory = $this->getMockBuilder(MessageFactoryInterface::class)
            ->getMock();
        $this->driver = $this->getMockBuilder(AccountPartialUpdateDriverInterface::class)
            ->getMock();
        $this->processor = new AccountProcessor(
            $this->doctrineHelper,
            $this->logger,
            $this->messageFactory,
            $this->driver
        );
    }

    public function testProcessWithAccount()
    {
        $data = ['id' => 1];
        $body = JSON::encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->never()))
            ->method('rollback');
        $em->expects(($this->once()))
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(BaseVisibilityResolved::class)
            ->willReturn($em);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMockBuilder(MessageInterface::class)
            ->getMock();
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $account = new Account();

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($account);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessReject()
    {
        $data = ['test' => 1];
        $body = JSON::encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(BaseVisibilityResolved::class)
            ->willReturn($em);
        $this->logger->expects($this->once())
            ->method('error');

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willThrowException(new InvalidArgumentException());

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMockBuilder(MessageInterface::class)
            ->getMock();
        $message->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessRequeue()
    {
        $data = ['test' => 1];
        $body = JSON::encode($data);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects(($this->once()))
            ->method('rollback');
        $em->expects(($this->never()))
            ->method('commit');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(BaseVisibilityResolved::class)
            ->willReturn($em);
        $this->logger->expects($this->once())
            ->method('error');

        $account = new Account();
        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($data)
            ->willReturn($account);
        $this->driver->expects($this->once())
            ->method('updateAccountVisibility')
            ->with($account)
            ->willThrowException(new \Exception());

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMockBuilder(MessageInterface::class)
            ->getMock();
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->getMockBuilder(SessionInterface::class)
            ->getMock();

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $session)
        );
    }
}
