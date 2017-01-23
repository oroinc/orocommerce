<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\RedirectBundle\Async\DirectUrlRemoveProcessor;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class DirectUrlRemoveProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var DirectUrlRemoveProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new DirectUrlRemoveProcessor($this->registry, $this->logger);
    }

    public function testProcessExceptionOutsideTransaction()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);
        $exception = new \Exception('Test');
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willThrowException($exception);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $exception]
            );

        $this->assertEquals(DirectUrlRemoveProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionInTransaction()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('rollback');

        $exception = new \Exception('Test');
        $repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('deleteSlugAttachedToEntityByClass')
            ->with($messageData)
            ->willThrowException($exception);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $exception]
            );

        $this->assertEquals(DirectUrlRemoveProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessNoEntityManagerFound()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(sprintf('Entity manager is not defined for class: "%s"', $messageData));

        $this->assertEquals(DirectUrlRemoveProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');

        $repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('deleteSlugAttachedToEntityByClass')
            ->with($messageData);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->assertEquals(DirectUrlRemoveProcessor::ACK, $this->processor->process($message, $session));
    }
}
