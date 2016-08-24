<?php

namespace Oro\Bundle\PricingBundle\Async;

use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
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
     * @param PriceListTriggerFactory $triggerFactory
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param ProductPriceBuilder $priceBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        ProductPriceBuilder $priceBuilder,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->assignmentBuilder = $assignmentBuilder;
        $this->priceBuilder = $priceBuilder;
        $this->triggerFactory = $triggerFactory;
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

        $this->assignmentBuilder->buildByPriceList($trigger->getPriceList());
        $this->priceBuilder->buildByPriceList($trigger->getPriceList(), $trigger->getProduct());

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_RULE];
    }
}
