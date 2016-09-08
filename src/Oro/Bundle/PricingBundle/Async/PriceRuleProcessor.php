<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
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
     * @var PriceListProductAssignmentBuilder
     */
    protected $assignmentBuilder;

    /**
     * @var ProductPriceBuilder
     */
    protected $priceBuilder;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var  PriceListRepository
     */
    protected $priceListRepository;

    /**
     * @param PriceListTriggerFactory $triggerFactory
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param ProductPriceBuilder $priceBuilder
     * @param LoggerInterface $logger
     * @param ManagerRegistry $registry
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        ProductPriceBuilder $priceBuilder,
        LoggerInterface $logger,
        ManagerRegistry $registry
    ) {
        $this->logger = $logger;
        $this->assignmentBuilder = $assignmentBuilder;
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
        $startTime = $trigger->getPriceList()->getUpdatedAt();
        $this->assignmentBuilder->buildByPriceList($trigger->getPriceList());
        $this->priceBuilder->buildByPriceList($trigger->getPriceList(), $trigger->getProduct());
        $this->getPriceListManager()->refresh($trigger->getPriceList());
        if ($startTime == $trigger->getPriceList()->getUpdatedAt()) {
            $this->getPriceListRepository()->updatePriceListsActuality([$trigger->getPriceList()], true);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_RULE];
    }

    /**
     * @return ObjectManager
     */
    protected function getPriceListManager()
    {
        if ($this->manager === null) {
            $this->manager = $this->registry->getManagerForClass(PriceList::class);
        }

        return $this->manager;
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        if ($this->priceListRepository === null) {
            $this->priceListRepository = $this->getPriceListManager()->getRepository(PriceList::class);
        }

        return $this->priceListRepository;
    }
}
