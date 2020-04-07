<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Handles price list related MQ topics.
 * Responsible for MQ message schedule, removing duplicates and sending messages to MQ.
 */
class PriceListTriggerHandler
{
    const PRICE_LISTS_KEY = 'price_lists';
    const PRODUCTS_KEY = 'products';
    public const BATCH_SIZE = 100;

    /**
     * @var PriceListTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var array
     */
    protected $triggersData = [];

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
     * @param array|Product[] $products
     */
    public function addTriggerForPriceList($topic, PriceList $priceList, array $products = [])
    {
        if ($priceList->isActive()) {
            if ($products) {
                foreach ($products as $product) {
                    if (!$this->isScheduledTrigger($topic, $priceList, $product)) {
                        $this->scheduleTrigger($topic, $priceList, $product);
                    }
                }
            } else {
                $this->scheduleTrigger($topic, $priceList);
            }
        }
    }

    /**
     * @param string $topic
     * @param PriceList[] $priceLists
     * @param array|Product[] $products
     */
    public function addTriggersForPriceLists($topic, array $priceLists, array $products = [])
    {
        foreach ($priceLists as $priceList) {
            $this->addTriggerForPriceList($topic, $priceList, $products);
        }
    }

    public function sendScheduledTriggers()
    {
        $this->removeDuplicatedData();
        foreach ($this->triggersData as $topic => $triggers) {
            if (array_key_exists(self::PRICE_LISTS_KEY, $triggers) && $triggers[self::PRICE_LISTS_KEY]) {
                $this->messageProducer->send(
                    $topic,
                    $this->triggerFactory->createFromIds(
                        array_fill_keys($triggers[self::PRICE_LISTS_KEY], [])
                    )
                );
            }
            if (array_key_exists(self::PRODUCTS_KEY, $triggers) && $triggers[self::PRODUCTS_KEY]) {
                $products = array_map(
                    function (array $products) {
                        return array_values($products);
                    },
                    $triggers[self::PRODUCTS_KEY]
                );

                $this->messageProducer->send(
                    $topic,
                    $this->triggerFactory->createFromIds($products)
                );
            }
        }
        $this->triggersData = [];
    }

    /**
     * Remove triggers that will lead to duplicate data processing.
     */
    protected function removeDuplicatedData()
    {
        // remove triggers by Product + Price List if trigger for Price List exists
        foreach ($this->triggersData as $topic => $triggers) {
            $filteredData = $triggers[self::PRODUCTS_KEY] ?? [];
            $priceListTriggers = $triggers[self::PRICE_LISTS_KEY] ?? [];
            if ($priceListTriggers) {
                $filteredData = array_filter(
                    $filteredData,
                    function ($priceListId) use ($priceListTriggers) {
                        return !isset($priceListTriggers[$priceListId]);
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
            $this->triggersData[$topic][self::PRODUCTS_KEY] = $filteredData;
        }

        // Remove price rules triggers if price list assignment trigger present,
        // because assignment calculation will trigger all rules rebuild
        if (array_key_exists(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, $this->triggersData)
            && array_key_exists(Topics::RESOLVE_PRICE_RULES, $this->triggersData)
        ) {
            $resolvePLs = $this->triggersData[Topics::RESOLVE_PRICE_RULES][self::PRICE_LISTS_KEY] ?? [];
            $assignPLs = $this->triggersData[Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS][self::PRICE_LISTS_KEY] ?? [];

            $this->triggersData[Topics::RESOLVE_PRICE_RULES][self::PRICE_LISTS_KEY] = array_diff(
                $resolvePLs,
                $assignPLs
            );
        }
    }

    /**
     * @param string $topic
     * @param PriceList $priceList
     * @param Product|int $product
     * @return bool
     */
    protected function isScheduledTrigger($topic, PriceList $priceList, $product = null)
    {
        $triggers = empty($this->triggersData[$topic]) ? [] : $this->triggersData[$topic];
        if ($product instanceof Product) {
            $product = $product->getId();
        }
        if ($product && isset($triggers[self::PRODUCTS_KEY][$priceList->getId()][$product])) {
            return true;
        }

        return isset($triggers[self::PRICE_LISTS_KEY][$priceList->getId()]);
    }

    /**
     * @param string $topic
     * @param PriceList $priceList
     * @param Product|int|null $product
     */
    protected function scheduleTrigger($topic, PriceList $priceList, $product = null)
    {
        $priceListId = $priceList->getId();
        if ($product) {
            if ($product instanceof Product) {
                $product = $product->getId();
            }
            $this->triggersData[$topic][self::PRODUCTS_KEY][$priceListId][$product] = $product;
        } else {
            $this->triggersData[$topic][self::PRICE_LISTS_KEY][$priceListId] = $priceListId;
        }
    }
}
