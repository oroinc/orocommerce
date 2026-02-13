<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateSinglePriceListPricesByRulesTopic;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Job task to generate prices for a single price list by assignment and price rules.
 *
 * This processor is a part of mass processing logic triggered by GenerateDependentPriceListsPricesProcessor.
 * Dependency updates, CPL re-combinations, and Flat pricing re-indexations are managed by the root processor logic.
 *
 * To trigger the full per-price list actions, including all rules and dependencies recalculations, use:
 *  - ResolvePriceListAssignedProductsTopic for assignment rule changes
 *  - ResolvePriceRulesTopic for price rules
 */
class GenerateSinglePriceListPricesByRulesProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private ManagerRegistry $doctrine,
        private PriceListProductAssignmentBuilder $assignmentBuilder,
        private ProductPriceBuilder $priceBuilder,
        private NotificationAlertManager $notificationAlertManager,
        private JobRunner $jobRunner
    ) {
    }

    #[\Override]
    public static function getSubscribedTopics()
    {
        return [GenerateSinglePriceListPricesByRulesTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = $message->getBody();

        try {
            $this->priceBuilder->setVersion($messageData['version']);

            $result = $this->jobRunner->runDelayed(
                $messageData['jobId'],
                function (JobRunner $jobRunner, Job $job) use ($messageData) {
                    return $this->processPriceList(
                        $messageData['priceListId'],
                        $messageData['products']
                    );
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (RetryableException $e) {
            $this->logger?->warning('Job redelivered', ['exception' => $e]);

            return self::REQUEUE;
        } finally {
            $this->priceBuilder->setVersion(null);
        }
    }

    private function processPriceList(int $priceListId, array $productIds): bool
    {
        $priceList = $this->doctrine->getRepository(PriceList::class)->find($priceListId);
        if (!$priceList) {
            $this->logger?->warning(
                'Price list not found.',
                ['priceListId' => $priceListId]
            );

            return true;
        }

        $em = $this->doctrine->getManagerForClass(PriceList::class);
        $em->beginTransaction();
        try {
            $startTime = $priceList->getUpdatedAt();

            $this->processRules($priceList, $productIds);
            $this->updatePriceListActuality($em, $priceList, $startTime);
            $this->resolveNotifications($priceList);

            $em->commit();

            return true;
        } catch (\Exception $e) {
            $em->rollback();

            if ($e instanceof RetryableException) {
                throw $e;
            }

            $this->logger?->error(
                'Unexpected exception occurred during Price List Assigned Products build.',
                ['exception' => $e]
            );
            $this->notificationAlertManager->addNotificationAlert(
                PriceListCalculationNotificationAlert::createForAssignedProductsBuildError(
                    $priceListId,
                    $e->getMessage()
                )
            );
            $this->notificationAlertManager->addNotificationAlert(
                PriceListCalculationNotificationAlert::createForPriceRulesBuildError(
                    $priceListId,
                    $e->getMessage()
                )
            );

            return false;
        }
    }

    private function processRules(PriceList $priceList, array $productIds): void
    {
        if ($priceList->getProductAssignmentRule()) {
            $this->assignmentBuilder->buildByPriceListWithoutEventDispatch($priceList, $productIds);
        }
        $this->priceBuilder->buildByPriceListWithoutTriggers($priceList, $productIds);
    }

    private function resolveNotifications(PriceList $priceList): void
    {
        if ($priceList->getProductAssignmentRule()) {
            $this->notificationAlertManager->resolveNotificationAlertByOperationAndItemIdForCurrentUser(
                PriceListCalculationNotificationAlert::OPERATION_ASSIGNED_PRODUCTS_BUILD,
                $priceList->getId()
            );
        }

        $this->notificationAlertManager->resolveNotificationAlertByOperationAndItemIdForCurrentUser(
            PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
            $priceList->getId()
        );
    }

    private function updatePriceListActuality(
        EntityManagerInterface $em,
        PriceList $priceList,
        \DateTime $startTime
    ): void {
        $em->refresh($priceList);
        if ($startTime == $priceList->getUpdatedAt()) {
            /** @var PriceListRepository $repo */
            $repo = $em->getRepository(PriceList::class);
            $repo->updatePriceListsActuality([$priceList], true);
        }
    }
}
