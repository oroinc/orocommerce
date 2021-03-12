<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
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
 * Resolve price lists rules and update actuality of price lists
 */
class PriceRuleProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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
     * @var ProductPriceBuilder
     */
    protected $priceBuilder;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var  PriceListRepository
     */
    protected $priceListRepository;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Messenger
     */
    protected $messenger;

    /**
     * @var PriceListTriggerHandler
     */
    private $triggerHandler;

    /**
     * @param PriceListTriggerFactory $triggerFactory
     * @param ProductPriceBuilder $priceBuilder
     * @param LoggerInterface $logger
     * @param ManagerRegistry $registry
     * @param Messenger $messenger
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        ProductPriceBuilder $priceBuilder,
        LoggerInterface $logger,
        ManagerRegistry $registry,
        Messenger $messenger,
        TranslatorInterface $translator
    ) {
        $this->logger = $logger;
        $this->priceBuilder = $priceBuilder;
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
        $this->messenger = $messenger;
        $this->translator = $translator;
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
        return [Topics::RESOLVE_PRICE_RULES];
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

        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(PriceList::class);
        $priceListsCount = count($trigger->getPriceListIds());
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
                    'Unexpected exception occurred during Price Rule build.',
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
                        Topics::RESOLVE_PRICE_RULES,
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
        $this->triggerHandler->sendScheduledTriggers();

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
            NotificationMessages::TOPIC_PRICE_RULES_BUILD,
            PriceList::class,
            $priceList->getId()
        );

        $startTime = $priceList->getUpdatedAt();

        $this->priceBuilder->buildByPriceListWithoutTriggerSend($priceList, $products);
        $this->updatePriceListActuality($priceList, $startTime);
    }

    /**
     * @param int $priceListId
     */
    private function onFailedPriceListId($priceListId)
    {
        $this->messenger->send(
            NotificationMessages::CHANNEL_PRICE_LIST,
            NotificationMessages::TOPIC_PRICE_RULES_BUILD,
            Message::STATUS_ERROR,
            $this->translator->trans('oro.pricing.notification.price_list.error.price_rule_build'),
            PriceList::class,
            $priceListId
        );
    }

    /**
     * @param PriceList $priceList
     * @param \DateTime $startTime
     */
    protected function updatePriceListActuality(PriceList $priceList, \DateTime $startTime)
    {
        $manager = $this->registry->getManagerForClass(PriceList::class);
        $manager->refresh($priceList);
        if ($startTime == $priceList->getUpdatedAt()) {
            /** @var PriceListRepository $repo */
            $repo = $manager->getRepository(PriceList::class);
            $repo->updatePriceListsActuality([$priceList], true);
        }
    }
}
