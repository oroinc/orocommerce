<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Combine prices for active and ready to rebuild Combined Price List based on the price version.
 */
class VersionedPriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private int $productsBatchSize = ProductPriceRepository::BUFFER_SIZE;

    private ManagerRegistry $doctrine;
    private JobRunner $jobRunner;
    private DependentJobService $dependentJob;
    private MessageProducerInterface $producer;
    private CombinedPriceListStatusHandlerInterface $statusHandler;
    private ShardManager $shardManager;

    public function __construct(
        ManagerRegistry $doctrine,
        JobRunner $jobRunner,
        DependentJobService $dependentJob,
        MessageProducerInterface $producer,
        CombinedPriceListStatusHandlerInterface $statusHandler,
        ShardManager $shardManager
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->dependentJob = $dependentJob;
        $this->producer = $producer;
        $this->statusHandler = $statusHandler;
        $this->shardManager = $shardManager;
    }

    public function setProductsBatchSize(int $productsBatchSize): void
    {
        $this->productsBatchSize = $productsBatchSize;
    }

    public static function getSubscribedTopics()
    {
        return [ResolveCombinedPriceByVersionedPriceListTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        if ($body === null) {
            return self::REJECT;
        }

        try {
            $version = $body['version'];
            $priceLists = $body['priceLists'];

            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($priceLists, $version) {
                    $combinedPriceLists = $this->getCombinedPriceListsByPriceList($priceLists);
                    $combinedPriceListIds = array_map(
                        fn (CombinedPriceList $cpl) => $cpl->getId(),
                        $combinedPriceLists
                    );

                    $this->schedulePostCplJobs($job, $combinedPriceListIds);
                    $this->addCplBuildActivity($job, $combinedPriceLists);

                    foreach ($combinedPriceLists as $combinedPriceList) {
                        $jobRunner->createDelayed(
                            sprintf('%s:cpl:%s', $job->getName(), $combinedPriceList->getName()),
                            function (JobRunner $jobRunner, Job $child) use ($version, $combinedPriceList) {
                                $products = $this->getProductsForCombinedPriceList($combinedPriceList, $version);
                                foreach ($products as $productBatch) {
                                    $this->producer->send(
                                        CombineSingleCombinedPriceListPricesTopic::getName(),
                                        [
                                            'cpl' => $combinedPriceList->getId(),
                                            'products' => $productBatch,
                                            'jobId' => $child->getId()
                                        ]
                                    );
                                }
                            }
                        );
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during Price Lists build.',
                ['exception' => $e]
            );

            return self::REJECT;
        }
    }

    private function schedulePostCplJobs(Job $job, array $cpls = []): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            RunCombinedPriceListPostProcessingStepsTopic::getName(),
            ['relatedJobId' => $job->getRootJob()->getId(), 'cpls' => $cpls]
        );
        $this->dependentJob->saveDependentJob($context);
    }

    private function getCombinedPriceListsByPriceList(array $priceListIds): array
    {
        $combinedPriceLists = $this
            ->getCombinedPriceListToPriceListRepository()
            ->getCombinedPriceListsByActualPriceLists($priceListIds);

        $activeCombinedPriceLists = [];
        foreach ($combinedPriceLists as $combinedPriceList) {
            if ($this->statusHandler->isReadyForBuild($combinedPriceList)) {
                $activeCombinedPriceLists[] = $combinedPriceList;
            }
        }

        return $activeCombinedPriceLists;
    }

    private function addCplBuildActivity(Job $job, array $combinedPriceLists): void
    {
        $combinedPriceListBuildActivityRepository = $this->getCombinedPriceListBuildActivityRepository();
        $combinedPriceListBuildActivityRepository->addBuildActivities($combinedPriceLists, $job->getRootJob()->getId());
    }

    private function getProductsForCombinedPriceList(CombinedPriceList $combinedPriceList, int $version): \Generator
    {
        $priceLists = $this->getCombinedPriceListToPriceListRepository()->getPriceListIdsByCpls([$combinedPriceList]);
        foreach ($priceLists as $priceList) {
            yield from $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList,
                $version,
                $this->productsBatchSize
            );
        }
    }

    private function getCombinedPriceListToPriceListRepository(): CombinedPriceListToPriceListRepository
    {
        return $this->doctrine->getRepository(CombinedPriceListToPriceList::class);
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->doctrine->getRepository(ProductPrice::class);
    }

    private function getCombinedPriceListBuildActivityRepository(): CombinedPriceListBuildActivityRepository
    {
        return $this->doctrine->getRepository(CombinedPriceListBuildActivity::class);
    }
}
