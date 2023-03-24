<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorage;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Responsible for updating the flat product price index based on price version.
 */
class VersionedFlatPriceProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private int $productsBatchSize = 500;

    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;
    private ManagerRegistry $doctrine;
    private ShardManager $shardManager;
    private ProductWebsiteReindexRequestDataStorage $dataStorage;

    public function __construct(
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        ManagerRegistry $doctrine,
        ShardManager $shardManager,
        ProductWebsiteReindexRequestDataStorage $dataStorage
    ) {
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->doctrine = $doctrine;
        $this->shardManager = $shardManager;
        $this->dataStorage = $dataStorage;
    }

    public static function getSubscribedTopics(): array
    {
        return [ResolveVersionedFlatPriceTopic::getName()];
    }

    public function setProductsBatchSize(int $productsBatchSize): void
    {
        $this->productsBatchSize = $productsBatchSize;
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $body = $message->getBody();
            $version = $body['version'];
            $priceLists = $body['priceLists'];

            $closure = fn (JobRunner $jobRunner, Job $job) => $this->doJob($job, $version, $priceLists);

            return $this->jobRunner->runUniqueByMessage($message, $closure) ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger?->error(
                'Unexpected exception occurred during queue message processing',
                ['exception' => $e, 'topic' => ResolveFlatPriceTopic::NAME]
            );

            return self::REJECT;
        }
    }

    private function doJob(Job $job, int $version, array $priceLists): bool
    {
        $products = $this->getProductsByVersion($priceLists, $version);
        foreach ($products as $batch) {
            $this->dataStorage->insertMultipleRequests($job->getId(), [], $batch);
        }

        $message = ['relatedJobId' => $job->getId(), 'indexationFieldsGroups' => ['pricing']];
        $this->producer->send(ReindexRequestItemProductsByRelatedJobIdTopic::NAME, $message);

        return true;
    }

    private function getProductsByVersion(array $priceListIds, int $version): \Generator
    {
        foreach ($priceListIds as $priceList) {
            yield from $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList,
                $version,
                $this->productsBatchSize
            );
        }
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->doctrine->getRepository(ProductPrice::class);
    }
}
