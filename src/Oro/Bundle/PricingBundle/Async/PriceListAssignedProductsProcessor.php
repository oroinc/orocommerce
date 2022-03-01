<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Compatibility\TopicAwareTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Updates combined price lists in case of price list product assigned rule is changed.
 */
class PriceListAssignedProductsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    use TopicAwareTrait;

    /** @var LoggerInterface */
    private $logger;

    /** @var PriceListProductAssignmentBuilder */
    private $assignmentBuilder;

    /** @var Messenger */
    private $messenger;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var PriceListTriggerHandler */
    private $triggerHandler;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        Messenger $messenger,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->assignmentBuilder = $assignmentBuilder;
        $this->messenger = $messenger;
        $this->translator = $translator;
    }

    public function setTriggerHandler(PriceListTriggerHandler $triggerHandler)
    {
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ResolvePriceListAssignedProductsTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $this->getResolvedBody($message, $this->logger);
        if ($body === null) {
            return self::REJECT;
        }
        $priceListsCount = count($body['product']);

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(PriceList::class);
        foreach ($body['product'] as $priceListId => $productIds) {
            /** @var PriceList|null $priceList */
            $priceList = $em->find(PriceList::class, $priceListId);
            if (null === $priceList) {
                $this->logger->warning(sprintf(
                    'PriceList entity with identifier %s not found.',
                    $priceListId
                ));
                continue;
            }

            $em->beginTransaction();
            try {
                $this->processPriceList($priceList, $productIds);
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $this->logger->error(
                    'Unexpected exception occurred during Price List Assigned Products build.',
                    ['exception' => $e]
                );

                if ($e instanceof RetryableException) {
                    // On RetryableException send back to queue the message related to a single price list
                    // that triggered an exception.
                    // If this was the only one PL in the message REQUEUE it to persist retries counter
                    if ($priceListsCount === 1) {
                        return self::REQUEUE;
                    }

                    $this->triggerHandler->handlePriceListTopic(
                        ResolvePriceListAssignedProductsTopic::getName(),
                        $priceList,
                        $productIds
                    );
                } else {
                    $this->onFailedPriceListId($priceList->getId());
                    if ($priceListsCount === 1) {
                        return self::REJECT;
                    }
                }
            }
        }

        return self::ACK;
    }

    /**
     * @param PriceList $priceList
     * @param int[] $productIds
     */
    private function processPriceList(PriceList $priceList, array $productIds): void
    {
        $this->messenger->remove(
            NotificationMessages::CHANNEL_PRICE_LIST,
            NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
            PriceList::class,
            $priceList->getId()
        );

        $this->assignmentBuilder->buildByPriceList($priceList, $productIds);
    }

    private function onFailedPriceListId(int $priceListId): void
    {
        $this->messenger->send(
            NotificationMessages::CHANNEL_PRICE_LIST,
            NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
            Message::STATUS_ERROR,
            $this->translator->trans('oro.pricing.notification.price_list.error.product_assignment_build'),
            PriceList::class,
            $priceListId
        );
    }
}
