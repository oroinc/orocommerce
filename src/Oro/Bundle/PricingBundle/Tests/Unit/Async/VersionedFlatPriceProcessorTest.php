<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Oro\Bundle\PricingBundle\Async\VersionedFlatPriceProcessor;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorage;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class VersionedFlatPriceProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var VersionedFlatPriceProcessor */
    private $processor;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var ProductWebsiteReindexRequestDataStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $productWebsiteReindexRequestDataStorage;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->productWebsiteReindexRequestDataStorage =
            $this->createMock(ProductWebsiteReindexRequestDataStorage::class);

        $this->processor = new VersionedFlatPriceProcessor(
            $this->producer,
            $this->jobRunner,
            $this->doctrine,
            $this->shardManager,
            $this->productWebsiteReindexRequestDataStorage
        );
    }

    public function testProcess(): void
    {
        $body = ['priceLists' => [1], 'version' => 1];

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([[1], [2], [3]]);

        $this->doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($productPriceRepository);

        $job = $this->createMock(Job::class);
        $job
            ->expects($this->any())
            ->method('getId')
            ->willReturn(123);
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

        $this->productWebsiteReindexRequestDataStorage
            ->expects($this->any())
            ->method('insertMultipleRequests')
            ->withConsecutive(
                [123, [], [1]],
                [123, [], [2]],
                [123, [], [3]]
            );

        $this->jobRunner
            ->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(function ($message, $closure) use ($jobRunner, $job) {
                return $closure($jobRunner, $job);
            });

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->withConsecutive(
                [ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
                    ['relatedJobId' => 123, 'indexationFieldsGroups' => ['pricing']]
                ],
            );

        $this->processor->setProductsBatchSize(1);
        $this->processor->process($this->getMessage($body), $this->getSession());
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }
}
