<?php

namespace OroB2B\Bundle\PricingBundle\Async;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use OroB2B\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceRuleTriggerFactory;
use Psr\Log\LoggerInterface;

class PriceRuleProcessor implements MessageProcessorInterface
{
    /**
     * @var PriceRuleTriggerFactory
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
     * @param PriceRuleTriggerFactory $triggerFactory
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param ProductPriceBuilder $priceBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceRuleTriggerFactory $triggerFactory,
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
}
