<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Oro\Bundle\PricingBundle\Async\FlatPriceProcessor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class FlatPriceProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FlatPriceProcessor */
    private $processor;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->processor = new FlatPriceProcessor($this->producer, $this->jobRunner);
    }

    public function testProcess(): void
    {
        $body = ['products' => [1,2,3]];

        $job = $this->createMock(Job::class);
        $job
            ->expects($this->any())
            ->method('getName')
            ->willReturn('job_name');
        $job
            ->expects($this->any())
            ->method('getId')
            ->willReturn('childId');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner
            ->expects($this->any())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($jobRunner, $job) {
                return $closure($jobRunner, $job);
            });

        $this->jobRunner
            ->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(function ($message, $closure) use ($jobRunner, $job) {
                return $closure($jobRunner, $job);
            });

        $this->producer
            ->expects($this->exactly(3))
            ->method('send')
            ->withConsecutive(
                [WebsiteSearchReindexTopic::getName(), $this->getReindexMessage(1)],
                [WebsiteSearchReindexTopic::getName(), $this->getReindexMessage(2)],
                [WebsiteSearchReindexTopic::getName(), $this->getReindexMessage(3)],
            );

        $this->processor->setProductsBatchSize(1);
        $this->processor->process($this->getMessage($body), $this->getSession());
    }

    private function getReindexMessage(int $productId): array
    {
        return [
            'jobId' => 'childId',
            'class' => Product::class,
            'context' => [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [$productId],
                AbstractIndexer::CONTEXT_FIELD_GROUPS => ['pricing']
            ]
        ];
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }
}
