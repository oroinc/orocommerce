<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheTopic;
use Oro\Bundle\RedirectBundle\Async\UrlCacheProcessor;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class UrlCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;
    use EntityTrait;

    private MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private SluggableUrlDumper|\PHPUnit\Framework\MockObject\MockObject $dumper;

    private UrlCacheProcessor $processor;

    protected function setUp(): void
    {
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->dumper = $this->createMock(SluggableUrlDumper::class);

        $this->processor = new UrlCacheProcessor($this->messageFactory, $this->dumper);
        $this->setUpLoggerMock($this->processor);
    }

    public function testProcess(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $data = ['class' => Product::class, 'entity_ids' => [1]];
        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::atLeastOnce())
            ->method('getBody')
            ->willReturn($data);

        $entity = $this->getEntity(Product::class, ['id' => 1]);
        $this->messageFactory->expects(self::once())
            ->method('getEntitiesFromMessage')
            ->willReturn([$entity]);

        $this->dumper->expects(self::once())
            ->method('dump')
            ->with($entity);

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [CalculateSlugCacheTopic::getName()],
            UrlCacheProcessor::getSubscribedTopics()
        );
    }
}
