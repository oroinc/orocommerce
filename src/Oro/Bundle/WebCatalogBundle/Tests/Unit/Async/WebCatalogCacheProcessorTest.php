<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class WebCatalogCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var WebCatalogCacheProcessor
     */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->processor = new WebCatalogCacheProcessor(
            $this->jobRunner,
            $this->producer,
            $this->registry,
            $this->configManager,
            $logger
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::CALCULATE_WEB_CATALOG_CACHE], $this->processor->getSubscribedTopics());
    }

    /**
     * @dataProvider webCatalogDataProvider
     *
     * @param string|array $messageBody
     */
    public function testProcess($messageBody)
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMessage('mid-42', $messageBody);

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $websites = [1 => $website1, 2 => $website2];
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $webCatalogRepository = $this->createMock(WebCatalogRepository::class);
        $webCatalogRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '1'])
            ->willReturn($webCatalog);
        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->expects($this->once())
            ->method('getAllWebsites')
            ->willReturn($websites);
        $contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $contentNodeRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 3])
            ->willReturn($node);
        $contentNodeRepo->expects($this->once())
            ->method('findBy')
            ->with(['id' => [3]])
            ->willReturn([$node]);

        $this->configManager
            ->expects($this->once())
            ->method('getValues')
            ->with('oro_web_catalog.web_catalog', $websites)
            ->willReturn([1 => 1, 2 => 3]);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.navigation_root', false, false, $website1)
            ->willReturn(3);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->withConsecutive(
                [WebCatalog::class],
                [Website::class],
                [ContentNode::class],
                [ContentNode::class]
            )
            ->willReturnOnConsecutiveCalls(
                $webCatalogRepository,
                $websiteRepository,
                $contentNodeRepo,
                $contentNodeRepo
            );
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertProcessCalled($node);
        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function webCatalogDataProvider()
    {
        return [
            'scalar' => [
                'messageBody' => '1'
            ],
            'array' => [
                'messageBody' => '{"webCatalogId": 1}'
            ],
            'array with string' => [
                'messageBody' => '{"webCatalogId": "1"}'
            ]
        ];
    }

    private function assertProcessCalled(ContentNode $node)
    {
        $this->producer->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_CONTENT_NODE_CACHE, ['contentNodeId' => $node->getId()]);
        $this->assertUniqueJobExecuted();
    }

    /**
     * @param string $messageId
     * @param string $body
     *
     * @return Message
     */
    private function createMessage($messageId, $body)
    {
        $message = new Message();
        $message->setMessageId($messageId);
        $message->setBody($body);

        return $message;
    }

    private function assertUniqueJobExecuted()
    {
        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $job = $this->createMock(Job::class);
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->willReturnCallback(
                function ($ownerId, $name, $closure) use ($job) {
                    $this->assertEquals('mid-42', $ownerId);
                    $this->assertEquals(Topics::CALCULATE_WEB_CATALOG_CACHE . ':1', $name);

                    return $closure($this->jobRunner, $job);
                }
            );
    }
}
