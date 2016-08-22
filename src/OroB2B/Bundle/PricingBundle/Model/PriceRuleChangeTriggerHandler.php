<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroB2B\Bundle\PricingBundle\Async\Topics;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Event\PriceRuleChange;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceRuleTrigger;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceRuleTriggerFactory;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class PriceRuleChangeTriggerHandler
{
    /**
     * @var PriceRuleTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var array|PriceRuleTrigger[]
     */
    protected $scheduledTriggers = [];

    /**
     * @param PriceRuleTriggerFactory $triggerFactory
     * @param MessageProducerInterface $messageProducer
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        PriceRuleTriggerFactory $triggerFactory,
        MessageProducerInterface $messageProducer,
        EventDispatcherInterface $dispatcher
    ) {
        $this->triggerFactory = $triggerFactory;
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
            $trigger = $this->triggerFactory->create($priceList, $product);
            $event = new GenericEvent($trigger);
            $this->dispatcher->dispatch(PriceRuleChange::NAME, $event);
            $trigger = $event->getSubject();

            if (!$this->isScheduledTrigger($trigger)) {
                $this->scheduleTrigger($trigger);
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

    public function sendScheduledTriggers()
    {
        if (count($this->scheduledTriggers) > 0) {
            $priceListTriggers = array_filter(
                $this->scheduledTriggers,
                function (PriceRuleTrigger $trigger) {
                    return !$trigger->getProduct();
                }
            );
            $filteredTriggers = array_filter(
                $this->scheduledTriggers,
                function (PriceRuleTrigger $trigger) use ($priceListTriggers) {
                    return !$trigger->getProduct()
                        || !array_key_exists($this->getKey($trigger->getPriceList()), $priceListTriggers);
                }
            );

            foreach ($filteredTriggers as $trigger) {
                $this->messageProducer->send(
                    Topics::CALCULATE_RULE,
                    $this->triggerFactory->triggerToArray($trigger)
                );
            }
            $this->scheduledTriggers = [];
        }
    }

    /**
     * @param PriceRuleTrigger $trigger
     * @return bool
     */
    protected function isScheduledTrigger(PriceRuleTrigger $trigger)
    {
        $priceList = $trigger->getPriceList();
        $product = $trigger->getProduct();

        return array_key_exists($this->getKey($priceList), $this->scheduledTriggers)
            || array_key_exists($this->getKey($priceList, $product), $this->scheduledTriggers);
    }

    /**
     * @param PriceRuleTrigger $trigger
     */
    protected function scheduleTrigger(PriceRuleTrigger $trigger)
    {
        $this->scheduledTriggers[$this->getKey($trigger->getPriceList(), $trigger->getProduct())] = $trigger;
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @return string
     */
    protected function getKey(PriceList $priceList, Product $product = null)
    {
        $key = 'pl' . $priceList->getId();
        if ($product) {
            $key .= ':p' . $product->getId();
        }

        return $key;
    }
}
