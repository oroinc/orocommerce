<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ActualizeCombinedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
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
 * Combine prices for a given list of Combined Price Lists.
 * Entry point job to run SingleCplProcessor.
 *
 * @internal Used to trigger CPL actualization by Price Debugging if needed
 */
class ActualizeCombinedPriceListsProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $doctrine;
    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;
    private DependentJobService $dependentJob;

    public function __construct(
        ManagerRegistry $doctrine,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        DependentJobService $dependentJob
    ) {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->dependentJob = $dependentJob;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ActualizeCombinedPriceListTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        try {
            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($body) {
                    $cpls = $body['cpl'];
                    $cplIds = array_map(fn (CombinedPriceList $cpl) => $cpl->getId(), $cpls);

                    $this->schedulePostCplJobs($job, $cplIds);
                    $this->addCplBuildActivity($job, $cpls);

                    foreach ($cpls as $cpl) {
                        $jobRunner->createDelayed(
                            sprintf('%s:cpl:%s', $job->getName(), $cpl->getName()),
                            function (JobRunner $jobRunner, Job $child) use ($cpl) {
                                $this->producer->send(
                                    CombineSingleCombinedPriceListPricesTopic::getName(),
                                    [
                                        'cpl' => $cpl->getId(),
                                        'jobId' => $child->getId()
                                    ]
                                );
                            }
                        );
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger?->error(
                'Unexpected exception occurred during Combined Price Lists build.',
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

    private function addCplBuildActivity(Job $job, array $activeCpls): void
    {
        /** @var CombinedPriceListBuildActivityRepository $repo */
        $repo = $this->doctrine->getRepository(CombinedPriceListBuildActivity::class);
        $repo->addBuildActivities($activeCpls, $job->getRootJob()->getId());
    }
}
