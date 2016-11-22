<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class PriceListTriggerHandler
{
    /**
     * @var PriceListTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var array|PriceListTrigger[]
     */
    protected $scheduledTriggers = [];

    /**
     * @param PriceListTriggerFactory $triggerFactory
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        PriceListTriggerFactory $triggerFactory,
        MessageProducerInterface $messageProducer
    ) {
        $this->triggerFactory = $triggerFactory;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param string $topic
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function addTriggerForPriceList($topic, PriceList $priceList, Product $product = null)
    {
        if ($priceList->isActive()) {
            $trigger = $this->triggerFactory->create($priceList, $product);

            if (!$this->isScheduledTrigger($topic, $trigger)) {
                $this->scheduleTrigger($topic, $trigger);
            }
        }
    }

    /**
     * @param $topic
     * @param PriceList[] $priceLists
     * @param Product|null $product
     */
    public function addTriggersForPriceLists($topic, array $priceLists, Product $product = null)
    {
        foreach ($priceLists as $priceList) {
            $this->addTriggerForPriceList($topic, $priceList, $product);
        }
    }

    public function sendScheduledTriggers()
    {
        foreach ($this->scheduledTriggers as $topic => $triggers) {
            if (count($triggers) > 0) {
                $priceListTriggers = array_filter(
                    $triggers,
                    function (PriceListTrigger $trigger) {
                        return !$trigger->getProduct();
                    }
                );

                /** @var PriceListTrigger[] $filteredTriggers */
                $filteredTriggers = array_filter(
                    $triggers,
                    function (PriceListTrigger $trigger) use ($priceListTriggers) {
                        return !$trigger->getProduct()
                        || !array_key_exists($this->getKey($trigger->getPriceList()), $priceListTriggers);
                    }
                );

                foreach ($filteredTriggers as $trigger) {
                    $this->messageProducer->send(
                        $topic,
                        $this->triggerFactory->triggerToArray($trigger)
                    );
                }
            }
        }
        $this->scheduledTriggers = [];
    }

    /**
     * @param string $topic
     * @param PriceListTrigger $trigger
     * @return bool
     */
    protected function isScheduledTrigger($topic, PriceListTrigger $trigger)
    {
        $priceList = $trigger->getPriceList();
        $product = $trigger->getProduct();

        $triggers = empty($this->scheduledTriggers[$topic]) ? [] : $this->scheduledTriggers[$topic];

        return array_key_exists($this->getKey($priceList), $triggers)
        || array_key_exists($this->getKey($priceList, $product), $triggers);
    }

    /**
     * @param string $topic
     * @param PriceListTrigger $trigger
     */
    protected function scheduleTrigger($topic, PriceListTrigger $trigger)
    {
        $this->scheduledTriggers[$topic][$this->getKey($trigger->getPriceList(), $trigger->getProduct())] = $trigger;
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
