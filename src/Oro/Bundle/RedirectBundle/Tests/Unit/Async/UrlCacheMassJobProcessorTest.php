<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Async\SluggableEntitiesProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Async\UrlCacheMassJobProcessor;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner as TestJobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class UrlCacheMassJobProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const MESSAGE_ID = 'some_message_id';

    /**
     * @var TestJobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var RoutingInformationProvider
     */
    private $routingInformationProvider;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var SluggableEntitiesProcessor
     */
    private $processor;

    /**
     * @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProvider::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);

        $this->processor = new UrlCacheMassJobProcessor(
            new TestJobRunner(),
            $this->producer,
            $this->createMock(ManagerRegistry::class),
            $this->logger = $this->createMock(LoggerInterface::class),
            $this->createMock(UrlCacheInterface::class)
        );
        $this->processor->setRoutingInformationProvider($this->routingInformationProvider);
        $this->processor->setMessageFactory($this->messageFactory);
    }

    /**
     * @param array $data
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMessage(array $data = [])
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getMessageId')
            ->willReturn(self::MESSAGE_ID);

        $messageBody = json_encode($data);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        return $message;
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testProcess()
    {
        $message = $this->createMessage();

        $this->routingInformationProvider->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn([Product::class, Category::class]);

        $message1 = ['class' => Product::class, 'id' => [], 'createRedirect' => false];
        $message2 = ['class' => Category::class, 'id' => [], 'createRedirect' => false];
        $this->messageFactory->expects($this->exactly(2))
            ->method('createMassMessage')
            ->withConsecutive(
                [Product::class, [], false],
                [Category::class, [], false]
            )
            ->willReturn(
                $message1,
                $message2
            );

        $this->producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::PROCESS_CALCULATE_URL_CACHE_JOB, $message1],
                [Topics::PROCESS_CALCULATE_URL_CACHE_JOB, $message2]
            );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createSession())
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::CALCULATE_URL_CACHE_MASS], UrlCacheMassJobProcessor::getSubscribedTopics());
    }
}
