<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
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

            $this->messenger->remove(
                NotificationMessages::CHANNEL_PRICE_LIST,
                NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                PriceList::class,
                $trigger->getPriceList()->getId()
            );

            $priceList = $trigger->getPriceList();
            $startTime = $priceList->getUpdatedAt();
            $this->priceBuilder->buildByPriceList($priceList, $trigger->getProduct());
            $this->updatePriceListActuality($priceList, $startTime);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during Price Rule build',
                ['exception' => $e]
            );
            if ($trigger && $trigger->getPriceList()) {
                $this->messenger->send(
                    NotificationMessages::CHANNEL_PRICE_LIST,
                    NotificationMessages::TOPIC_PRICE_RULES_BUILD,
                    Message::STATUS_ERROR,
                    $this->translator->trans('oro.pricing.notification.price_list.error.price_rule_build'),
                    PriceList::class,
                    $trigger->getPriceList()->getId()
                );
            }

            return self::REJECT;
        }

        return self::ACK;
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
