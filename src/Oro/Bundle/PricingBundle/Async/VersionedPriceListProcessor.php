<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
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

    private PriceListRelationTriggerHandler $priceListRelationTriggerHandler;

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

    public function setPriceListRelationTriggerHandler(PriceListRelationTriggerHandler $handler): void
    {
        $this->priceListRelationTriggerHandler = $handler;
    }

    public function setProductsBatchSize(int $productsBatchSize): void
    {
        $this->productsBatchSize = $productsBatchSize;
    }

    #[\Override]
    public static function getSubscribedTopics()
    {
        return [ResolveCombinedPriceByVersionedPriceListTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        if ($body === null) {
            return self::REJECT;
        }

        try {
            if ($this->producer instanceof BufferedMessageProducer) {
                $this->producer->disableBuffering();
            }

            $version = $body['version'];
            $priceLists = $body['priceLists'];

            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($priceLists, $version) {
                    $this->schedulePostCplJobs($job);

                    $priceListsWithAllNewPrices = $this->triggerCplRebuildMessagesForPreviouslyEmptyPriceLists(
                        $priceLists,
                        $version
                    );

                    // Filter out price lists that have all new prices.
                    $priceLists = array_filter(
                        $priceLists,
                        static fn (int $priceListId) => empty($priceListsWithAllNewPrices[$priceListId])
                    );
                    if ($priceLists) {
                        $this->addCplRecombinationJobs($job, $jobRunner, $priceLists, $version);
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
        } finally {
            if ($this->producer instanceof BufferedMessageProducer) {
                $this->producer->enableBuffering();
            }
        }
    }

    private function addCplRecombinationJobs(Job $job, JobRunner $jobRunner, array $priceListId, int $version): void
    {
        $combinedPriceLists = $this->getCombinedPriceListsByPriceList($priceListId);
        $this->addCplBuildActivity($job, $combinedPriceLists);
        foreach ($combinedPriceLists as $combinedPriceList) {
            $products = $this->getProductsForCombinedPriceList($combinedPriceList, $version);
            $batchNum = 0;
            foreach ($products as $productBatch) {
                $jobRunner->createDelayed(
                    sprintf('%s:cpl:%s:batch:%d', $job->getName(), $combinedPriceList->getName(), $batchNum++),
                    function (JobRunner $jobRunner, Job $child) use ($productBatch, $combinedPriceList) {
                        $this->producer->send(
                            CombineSingleCombinedPriceListPricesTopic::getName(),
                            [
                                'cpl' => $combinedPriceList->getId(),
                                'products' => $productBatch,
                                'jobId' => $child->getId()
                            ]
                        );
                    }
                );
            }
        }
    }

    private function triggerCplRebuildMessagesForPreviouslyEmptyPriceLists(array $priceListIds, int $version): array
    {
        $emptyPls = [];
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(PriceList::class);
        $repo = $this->getProductPriceRepository();
        foreach ($priceListIds as $priceListId) {
            /** @var PriceList $priceList */
            $priceList = $em->getReference(PriceList::class, $priceListId);
            if ($repo->areAllVersionedPricesNewInPriceList($this->shardManager, $priceList, $version)) {
                $this->priceListRelationTriggerHandler->handlePriceListStatusChange($priceList);
                $emptyPls[$priceList->getId()] = true;
            }
        }

        return $emptyPls;
    }

    private function schedulePostCplJobs(Job $job): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            RunCombinedPriceListPostProcessingStepsTopic::getName(),
            ['relatedJobId' => $job->getRootJob()->getId()]
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
