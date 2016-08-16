<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroB2B\Bundle\PricingBundle\Async\Message\PriceRuleCalculateMessageFactory;
use OroB2B\Bundle\PricingBundle\Async\Topics;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Async\Message\PriceRuleCalculateMessage;
use OroB2B\Bundle\PricingBundle\Event\PriceRuleChange;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceRuleChangeTriggerHandler
{
    /**
     * @var PriceRuleCalculateMessageFactory
     */
    protected $messageFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var array|PriceRuleCalculateMessage[]
     */
    protected $scheduledMessages = [];

    /**
     * @param PriceRuleCalculateMessageFactory $messageFactory
     * @param MessageProducerInterface $messageProducer
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        PriceRuleCalculateMessageFactory $messageFactory,
        MessageProducerInterface $messageProducer,
        EventDispatcherInterface $dispatcher
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageProducer = $messageProducer;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function addTriggersForPriceList(PriceList $priceList, Product $product = null)
    {
        if ($priceList->isActive()) {
            $message = $this->messageFactory->create($priceList, $product);
            $this->dispatcher->dispatch(PriceRuleChange::NAME, $message);

            if (!$this->isScheduledMessageWithPriceList($priceList, $product)) {
                $this->scheduleMessage($message);
            }
        }
    }

    /**
     * @param PriceList[] $priceLists
     * @param Product|null $product
     */
    public function addTriggersForPriceLists(array $priceLists, Product $product = null)
    {
        foreach ($priceLists as $priceList) {
            $this->addTriggersForPriceList($priceList, $product);
        }
    }

    public function sendScheduledMessages()
    {
        if (count($this->scheduledMessages) > 0) {
            foreach ($this->scheduledMessages as $message) {
                $this->messageProducer->send(
                    Topics::CALCULATE_RULE,
                    $this->messageFactory->messageToArray($message)
                );
            }
        }
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     * @return bool
     */
    protected function isScheduledMessageWithPriceList(PriceList $priceList, Product $product = null)
    {
        foreach ($this->scheduledMessages as $message) {
            // Skip message processing if there is message for whole price list
            // or message for same product and price list already scheduled
            if ((!$message->getProduct() && $message->getPriceList()->getId() === $priceList->getId())
                || ($product && $message->getProduct() && $message->getProduct()->getId() === $product->getId()
                    && $message->getPriceList()->getId() === $priceList->getId()
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PriceRuleCalculateMessage $message
     */
    protected function scheduleMessage(PriceRuleCalculateMessage $message)
    {
        $this->scheduledMessages[] = $message;
    }
}
