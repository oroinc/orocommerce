<?php

namespace OroB2B\Bundle\PricingBundle\Async;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use OroB2B\Bundle\PricingBundle\Async\Message\Exception\InvalidMessageException;
use OroB2B\Bundle\PricingBundle\Async\Message\PriceRuleCalculateMessageFactory;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Psr\Log\LoggerInterface;

class PriceRuleProcessor implements MessageProcessorInterface
{
    /**
     * @var PriceRuleCalculateMessageFactory
     */
    protected $messageFactory;

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
     * @param PriceRuleCalculateMessageFactory $messageFactory
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     * @param ProductPriceBuilder $priceBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        PriceRuleCalculateMessageFactory $messageFactory,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        ProductPriceBuilder $priceBuilder,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->assignmentBuilder = $assignmentBuilder;
        $this->priceBuilder = $priceBuilder;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $queueMessage, SessionInterface $session)
    {
        try {
            $message = $this->messageFactory->createFromQueueMessage($queueMessage);
        } catch (InvalidMessageException $e) {
            $this->logger->error($e->getMessage());

            return self::REJECT;
        }

        $this->assignmentBuilder->buildByPriceList($message->getPriceList());
        $this->priceBuilder->buildByPriceList($message->getPriceList(), $message->getProduct());

        return self::ACK;
    }
}
