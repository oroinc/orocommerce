<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

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
     * @param PriceListTriggerFactory $triggerFactory
     * @param ProductPriceBuilder $priceBuilder
     * @param LoggerInterface $logger
     * @param ManagerRegistry $registry
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        ProductPriceBuilder $priceBuilder,
        LoggerInterface $logger,
        ManagerRegistry $registry
    ) {
        $this->logger = $logger;
        $this->priceBuilder = $priceBuilder;
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        }

        $priceList = $trigger->getPriceList();
        $startTime = $priceList->getUpdatedAt();

        $this->priceBuilder->buildByPriceList($priceList, $trigger->getProduct());
        $this->updatePriceListActuality($priceList, $startTime);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_PRICE_RULES];
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
