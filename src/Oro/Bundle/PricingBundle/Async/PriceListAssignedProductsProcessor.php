<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
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

    /**
     * @param ManagerRegistry                   $doctrine
     * @param LoggerInterface                   $logger
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param Messenger                         $messenger
     * @param TranslatorInterface               $translator
     */
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
        $body = JSON::decode($message->getBody());
        if (!isset($body['product']) || !\is_array($body['product'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(PriceList::class);
        $em->beginTransaction();
        try {
            foreach ($body['product'] as $priceListId => $productIds) {
                /** @var PriceList|null $priceList */
                $priceList = $em->find(PriceList::class, $priceListId);
                if (null === $priceList) {
                    throw new EntityNotFoundException(sprintf(
                        'PriceList entity with identifier %s not found.',
                        $priceListId
                    ));
                }
                $this->processPriceList($priceList, $productIds);
            }

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Price List Assigned Products build.',
                ['exception' => $e]
            );
            if (!empty($priceListId)) {
                $this->onFailedPriceListId($priceListId);
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @param PriceList $priceList
     * @param int[]     $productIds
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

    /**
     * @param int $priceListId
     */
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
