<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Event\AfterSaveJobEvent;
use Oro\Component\MessageQueue\Job\Job;

/**
 * Send ProductPrice related MQ messages after mass prices update via Batch API.
 */
final class AfterSaveMqJobListener
{
    private const OPERATION_ID = 'api_operation_id';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ShardManager $shardManager,
        private readonly MessageProducerInterface $producer
    ) {
    }

    public function onAfterSave(AfterSaveJobEvent $event): void
    {
        $job = $event->getJob();
        if ($job->getStatus() !== Job::STATUS_SUCCESS) {
            return;
        }

        if (!$job->isRoot()) {
            return;
        }

        $data = $job->getData();
        if (!isset($data[self::OPERATION_ID])) {
            return;
        }

        if ($this->shardManager->isShardingEnabled()) {
            return;
        }

        $operationId = $data[self::OPERATION_ID];
        $operation = $this->doctrine
            ->getManagerForClass(AsyncOperation::class)
            ->find(AsyncOperation::class, $operationId);
        if (null === $operation) {
            return;
        }

        if ($operation->getEntityClass() !== ProductPrice::class) {
            return;
        }

        /** @var ProductPriceRepository $repo */
        $repo = $this->doctrine->getRepository(ProductPrice::class);
        foreach ($repo->getPriceListIdsAffectedByVersion($operationId) as $priceListId) {
            $this->producer->send(
                GenerateDependentPriceListPricesTopic::getName(),
                [
                    'sourcePriceListId' => $priceListId,
                    'version' => $operationId
                ]
            );
        }
    }
}
