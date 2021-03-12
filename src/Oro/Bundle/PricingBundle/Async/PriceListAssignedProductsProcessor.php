<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Updates combined price lists in case of price list product assigned rule is changed.
 */
class PriceListAssignedProductsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var PriceListTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PriceListProductAssignmentBuilder
     */
    protected $assignmentBuilder;

    /**
     * @var Messenger
     */
    protected $messenger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param PriceListTriggerFactory $triggerFactory
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param LoggerInterface $logger
     * @param Messenger $messenger
     * @param TranslatorInterface $translator
     * @param ManagerRegistry $registry
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        LoggerInterface $logger,
        Messenger $messenger,
        TranslatorInterface $translator,
        ManagerRegistry $registry
    ) {
        $this->logger = $logger;
        $this->assignmentBuilder = $assignmentBuilder;
        $this->triggerFactory = $triggerFactory;
        $this->messenger = $messenger;
        $this->translator = $translator;
        $this->registry = $registry;
    }

    /**
     * @param PriceListTriggerHandler $triggerHandler
     */
    public function setTriggerHandler(PriceListTriggerHandler $triggerHandler)
    {
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $trigger = null;
        try {
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        }

        $priceListsCount = count($trigger->getPriceListIds());
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(PriceList::class);

        foreach ($trigger->getProducts() as $priceListId => $productIds) {
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
                $this->triggerHandler->removeScheduledTriggersByPriceList($priceList);

                $this->logger->error(
                    'Unexpected exception occurred during Price List Assigned Products build',
                    ['exception' => $e]
                );

                if ($e instanceof RetryableException) {
                    // On RetryableException send back to queue the message related to a single price list
                    // that triggered an exception.
                    // If this was the only one PL in the message REQUEUE it to persist retries counter
                    if ($priceListsCount === 1) {
                        return self::REQUEUE;
                    }

                    $this->triggerHandler->addTriggerForPriceList(
                        Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
                        $priceList,
                        $productIds
                    );
                    $this->triggerHandler->sendScheduledTriggers();
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
     * @param array $products
     */
    private function processPriceList(PriceList $priceList, array $products)
    {
        $this->messenger->remove(
            NotificationMessages::CHANNEL_PRICE_LIST,
            NotificationMessages::TOPIC_ASSIGNED_PRODUCTS_BUILD,
            PriceList::class,
            $priceList->getId()
        );

        $this->assignmentBuilder->buildByPriceList($priceList, $products);
    }

    /**
     * @param int $priceListId
     */
    private function onFailedPriceListId($priceListId)
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
