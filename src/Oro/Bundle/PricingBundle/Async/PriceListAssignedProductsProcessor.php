<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(PriceList::class);
        $em->beginTransaction();
        
        $trigger = null;
        try {
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);

            $repository = $em->getRepository(PriceList::class);
            foreach ($trigger->getProducts() as $plId => $plProducts) {
                $this->processPriceList($repository->find($plId), $plProducts);
            }

            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Price List Assigned Products build',
                ['exception' => $e]
            );
            if ($trigger && !empty($plId)) {
                $this->onFailedPriceListId($plId);
            }

            return self::REJECT;
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
