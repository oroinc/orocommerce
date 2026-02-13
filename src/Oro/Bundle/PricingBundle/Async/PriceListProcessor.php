<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
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
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 * Receives message in format: array{'product': array{(priceListId)int: list<(productId)int>}
 */
class PriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $doctrine;
    private CombinedPriceListStatusHandlerInterface $statusHandler;
    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;
    private DependentJobService $dependentJob;
    private int $productsBatchSize = ProductPriceRepository::BUFFER_SIZE;

    public function __construct(
        ManagerRegistry $doctrine,
        CombinedPriceListStatusHandlerInterface $statusHandler,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        DependentJobService $dependentJob
    ) {
        $this->doctrine = $doctrine;
        $this->statusHandler = $statusHandler;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->dependentJob = $dependentJob;
    }

    public function setProductsBatchSize(int $productsBatchSize): void
    {
        $this->productsBatchSize = $productsBatchSize;
    }

    #[\Override]
    public static function getSubscribedTopics()
    {
        return [ResolveCombinedPriceByPriceListTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        try {
            if ($this->producer instanceof BufferedMessageProducer) {
                $this->producer->disableBuffering();
            }

            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($body) {
                    $cpl2plRepository = $this->doctrine->getRepository(CombinedPriceListToPriceList::class);
                    $allProducts = $body['product'];
                    $activeCpls = $this->getActiveCPlsByPls($cpl2plRepository, $allProducts);

                    $this->schedulePostCplJobs($job);
                    $this->addCplBuildActivity($job, $activeCpls);

                    foreach ($activeCpls as $cpl) {
                        $products = $this->getProductsForCombinedPriceList(
                            $cpl2plRepository,
                            $cpl,
                            $allProducts
                        );

                        $batchNum = 0;
                        foreach ($this->getProductBatches($products) as $productBatch) {
                            $jobRunner->createDelayed(
                                sprintf('%s:cpl:%s:batch:%d', $job->getName(), $cpl->getName(), $batchNum++),
                                function (JobRunner $jobRunner, Job $child) use ($cpl, $productBatch) {
                                    $this->producer->send(
                                        CombineSingleCombinedPriceListPricesTopic::getName(),
                                        [
                                            'cpl' => $cpl->getId(),
                                            'products' => $productBatch,
                                            'jobId' => $child->getId()
                                        ]
                                    );
                                }
                            );
                        }
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger?->error(
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

    private function schedulePostCplJobs(Job $job): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            RunCombinedPriceListPostProcessingStepsTopic::getName(),
            ['relatedJobId' => $job->getRootJob()->getId()]
        );
        $this->dependentJob->saveDependentJob($context);
    }

    private function getActiveCPlsByPls(
        CombinedPriceListToPriceListRepository $cpl2plRepository,
        array $allProducts
    ): array {
        $cpls = $cpl2plRepository->getCombinedPriceListsByActualPriceLists(array_keys($allProducts));
        $activeCpls = [];
        foreach ($cpls as $cpl) {
            if ($this->statusHandler->isReadyForBuild($cpl)) {
                $activeCpls[] = $cpl;
            }
        }

        return $activeCpls;
    }

    private function getProductsForCombinedPriceList(
        CombinedPriceListToPriceListRepository $cpl2plRepository,
        CombinedPriceList $cpl,
        array $allProducts
    ): array {
        $pls = $cpl2plRepository->getPriceListIdsByCpls([$cpl]);

        return array_merge(...array_intersect_key($allProducts, array_flip($pls)));
    }

    private function addCplBuildActivity(Job $job, array $activeCpls): void
    {
        /** @var CombinedPriceListBuildActivityRepository $repo */
        $repo = $this->doctrine->getRepository(CombinedPriceListBuildActivity::class);
        $repo->addBuildActivities($activeCpls, $job->getRootJob()->getId());
    }

    private function getProductBatches(array $products): \Generator
    {
        if (!$products) {
            yield [];
        } else {
            yield from array_chunk($products, $this->productsBatchSize);
        }
    }
}
