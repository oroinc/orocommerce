<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 * Receives message in format: array{'product': array{(priceListId)int: list<(productId)int>}
 */
class SingleCplProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;
    private ManagerRegistry $doctrine;
    private CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade;
    private CombinedPriceListTriggerHandler $indexationTriggerHandler;
    private CombinedPriceListStatusHandlerInterface $statusHandler;
    private EventDispatcherInterface $dispatcher;
    private CombinedPriceListScheduleResolver $scheduleResolver;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $doctrine,
        CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade,
        CombinedPriceListTriggerHandler $indexationTriggerHandler,
        CombinedPriceListStatusHandlerInterface $statusHandler,
        EventDispatcherInterface $dispatcher,
        CombinedPriceListScheduleResolver $scheduleResolver
    ) {
        $this->jobRunner = $jobRunner;
        $this->doctrine = $doctrine;
        $this->combinedPriceListsBuilderFacade = $combinedPriceListsBuilderFacade;
        $this->indexationTriggerHandler = $indexationTriggerHandler;
        $this->statusHandler = $statusHandler;
        $this->dispatcher = $dispatcher;
        $this->scheduleResolver = $scheduleResolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [CombineSingleCombinedPriceListPricesTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = $message->getBody();
        if (false === $messageData['cpl']) {
            // Redeliver message to process it again if there was retryable database exception or
            // when some record was removed by another transaction and foreign key constraint failed on insert
            $this->logger?->warning(
                'Unexpected retryable exception occurred during Combined Price Lists message resolving.',
                ['topic' => CombineSingleCombinedPriceListPricesTopic::getName()]
            );

            return self::REQUEUE;
        }

        try {
            $result = $this->jobRunner->runDelayed(
                $messageData['jobId'],
                function (JobRunner $jobRunner, Job $job) use ($messageData) {
                    return $this->processJob($job, $messageData);
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (JobRedeliveryException $e) {
            return self::REQUEUE;
        }
    }

    private function processJob(Job $job, array $messageData): bool
    {
        if (null === $messageData['cpl']) {
            return true;
        }

        /** @var CombinedPriceList $cpl */
        $cpl = $messageData['cpl'];
        $products = $messageData['products'];
        $assignTo = $messageData['assign_to'];
        $version = $messageData['version'];

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(CombinedPriceList::class);
        $em->beginTransaction();
        $this->indexationTriggerHandler->startCollect($job->getRootJob()->getId());
        try {
            $this->buildCombinedPriceList($cpl, $products, $assignTo);
            $this->combinedPriceListsBuilderFacade->processAssignments($cpl, $assignTo, $version);
            $this->removeActivityRecords($cpl, $job->getRootJob()->getId(), empty($products));

            // Indexation requests are collected here and not triggered immediately, so we should write these
            // requests within an active DB transaction. If the actual indexation will be triggered here this call
            // should be moved after DB transaction commit to be sure that all prices are written.
            $this->indexationTriggerHandler->commit();
            $em->commit();

            return true;
        } catch (\Exception $e) {
            $this->indexationTriggerHandler->rollback();
            $em->rollback();

            if ($e instanceof RetryableException || $e instanceof ForeignKeyConstraintViolationException) {
                // Redeliver message to process it again if there was retryable database exception or
                // when some record was removed by another transaction and foreign key constraint failed on insert
                $this->logger?->warning(
                    'Unexpected retryable database exception occurred during Combined Price Lists build.',
                    [
                        'topic' => CombineSingleCombinedPriceListPricesTopic::getName(),
                        'exception' => $e
                    ]
                );

                throw JobRedeliveryException::create();
            }
            $this->logger?->error(
                'Unexpected exception occurred during Combined Price Lists build.',
                [
                    'topic' => CombineSingleCombinedPriceListPricesTopic::getName(),
                    'exception' => $e
                ]
            );
        }

        return false;
    }

    /**
     * Remove lock information about CPL is scheduled for prices rebuild.
     * After this lock removal the CPL will be available to be used as a fallback CPL.
     */
    private function removeActivityRecords(CombinedPriceList $cpl, int $jobId, bool $removeAllActivities): void
    {
        $activityRecordsRepo = $this->doctrine->getRepository(CombinedPriceListBuildActivity::class);
        if ($removeAllActivities) {
            $activityRecordsRepo->deleteActivityRecordsForCombinedPriceList($cpl);
        } else {
            $activityRecordsRepo->deleteActivityRecordsForJob($jobId);
        }
    }

    /**
     * If CPL is ready for build - perform build tasks. Otherwise, when CPL full rebuild requested (no products passed)
     * try to find current active CPL for a given CPL and build it instead to prevent switching to not built CPL.
     */
    private function buildCombinedPriceList(CombinedPriceList $cpl, array $products, array $assignTo): void
    {
        if ($this->statusHandler->isReadyForBuild($cpl)) {
            $this->performCombinedPriceListBuild($cpl, $products, $assignTo);
        } elseif (!$products) {
            $activeCpl = $this->scheduleResolver->getActiveCplByFullCPL($cpl);
            if ($activeCpl && !$activeCpl->isPricesCalculated() && $activeCpl->getId() !== $cpl->getId()) {
                $this->performCombinedPriceListBuild($activeCpl, [], $assignTo);
            }
        }
    }

    /**
     * Run prices combination, add product indexation requests, and trigger CombinedPriceListsUpdateEvent.
     */
    private function performCombinedPriceListBuild(CombinedPriceList $cpl, array $products, array $assignTo): void
    {
        $this->combinedPriceListsBuilderFacade->rebuild([$cpl], $products);
        $this->combinedPriceListsBuilderFacade->triggerProductIndexation($cpl, $assignTo, $products);
        $this->dispatcher->dispatch(
            new CombinedPriceListsUpdateEvent([$cpl->getId()]),
            CombinedPriceListsUpdateEvent::NAME
        );
    }
}
