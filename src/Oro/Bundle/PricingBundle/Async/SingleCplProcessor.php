<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Compatibility\TopicAwareTrait;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListActivationStatusHelperInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Combine prices for active and ready to rebuild Combined Price List for a given list of price lists and products.
 * Receives message in format: array{'product': array{(priceListId)int: list<(productId)int>}
 */
class SingleCplProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use TopicAwareTrait;

    private JobRunner $jobRunner;
    private ManagerRegistry $doctrine;
    private CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade;
    private CombinedPriceListTriggerHandler $indexationTriggerHandler;
    private CombinedPriceListActivationStatusHelperInterface $activationStatusHelper;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        JobRunner $jobRunner,
        ManagerRegistry $doctrine,
        CombinedPriceListsBuilderFacade $combinedPriceListsBuilderFacade,
        CombinedPriceListTriggerHandler $indexationTriggerHandler,
        CombinedPriceListActivationStatusHelperInterface $activationStatusHelper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->jobRunner = $jobRunner;
        $this->doctrine = $doctrine;
        $this->combinedPriceListsBuilderFacade = $combinedPriceListsBuilderFacade;
        $this->indexationTriggerHandler = $indexationTriggerHandler;
        $this->activationStatusHelper = $activationStatusHelper;
        $this->dispatcher = $dispatcher;
        $this->logger = new NullLogger();
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
        $messageData = $this->getResolvedBody($message, $this->logger);
        if ($messageData === null) {
            return self::REJECT;
        }

        $jobId = $messageData['jobId'];
        $result = $this->jobRunner->runDelayed($jobId, function (JobRunner $jobRunner, Job $job) use ($messageData) {
            if (empty($messageData['cpl'])) {
                return true;
            }

            /** @var CombinedPriceList $cpl */
            $cpl = $messageData['cpl'];
            $products = $messageData['products'];
            $assignTo = $messageData['assign_to'];

            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManagerForClass(CombinedPriceList::class);
            $em->beginTransaction();
            $this->indexationTriggerHandler->startCollectVersioned($job->getRootJob()->getId());
            try {
                $this->buildCombinedPriceList($cpl, $products, $assignTo);
                $this->combinedPriceListsBuilderFacade->processAssignments($cpl, $assignTo);
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

                if ($e instanceof RetryableException) {
                    // Job runner will mark job as fail redelivered if exception is unprocessed.
                    throw $e;
                }
                $this->logger->error(
                    'Unexpected exception occurred during Combined Price Lists build.',
                    ['exception' => $e]
                );

                return false;
            }
        });

        return $result ? self::ACK : self::REJECT;
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
     * Run prices combination, add product indexation requests, and trigger CombinedPriceListsUpdateEvent
     * when CPL is ready for build.
     */
    private function buildCombinedPriceList(CombinedPriceList $cpl, array $products, array $assignTo): void
    {
        if (!$this->activationStatusHelper->isReadyForBuild($cpl)) {
            return;
        }

        $this->combinedPriceListsBuilderFacade->rebuildWithoutTriggers([$cpl], $products);
        $this->combinedPriceListsBuilderFacade->triggerProductIndexation($cpl, $assignTo, $products);
        $this->dispatcher->dispatch(
            new CombinedPriceListsUpdateEvent([$cpl->getId()]),
            CombinedPriceListsUpdateEvent::NAME
        );
    }
}
