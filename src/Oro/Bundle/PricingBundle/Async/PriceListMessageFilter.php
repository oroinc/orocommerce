<?php

namespace Oro\Bundle\PricingBundle\Async;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;

/**
 * The filter for price management related topics that does the following:
 * * removes messages by price list + products if a message by the corresponding price list exists
 * * removes resolve price rules messages if the corresponding price list assignment messages exist,
 *   because assignment calculation will trigger all rules rebuild
 * * removes duplicated messages and duplicated data in messages
 * * splits messages contain items for price list and items for price list + products into different messages
 * * collapses messages from the same topic into one message
 *
 * @see \Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PriceListMessageFilter implements MessageFilterInterface
{
    private const PRODUCT = 'product';

    /** @var string[] */
    private $topics;

    /**
     * @param string[] $topics
     */
    public function __construct(array $topics)
    {
        $this->topics = $topics;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopics($this->topics)) {
            return;
        }

        $this->removeRedundantAndDuplicatedMessages($buffer);
        $this->splitMixedMessages($buffer);
        $this->collapseMessages($buffer);
    }

    private function removeRedundantAndDuplicatedMessages(MessageBuffer $buffer): void
    {
        $priceListMap = [];
        $priceListDupMap = [];
        $productMap = [];
        $productDupMap = [];
        $this->collectData($buffer, $priceListMap, $priceListDupMap, $productMap, $productDupMap);
        $this->removeRedundantProductMessages($buffer, $priceListMap, $productMap, $productDupMap);
        $this->removeDuplicatedMessagesForPriceLists($buffer, $priceListDupMap);
        $this->removeDuplicatedMessagesForProducts($buffer, $productDupMap);
        $this->removeRedundantMessagesForResolvePriceRules($buffer);
    }

    /**
     * Collects information about price management related messages.
     *
     * @param MessageBuffer $buffer
     * @param array $priceListMap [topic => [priceListId => [messageId, ...], ...], ...]
     * @param array $priceListDupMap [topic => [priceListId => [messageId, ...], ...], ...]
     * @param array $productMap [topic => [priceListId => [productId => [messageId, ...], ...], ...], ...]
     * @param array $productDupMap [topic => [priceListId => [productId => [messageId, ...], ...], ...], ...]
     */
    private function collectData(
        MessageBuffer $buffer,
        array &$priceListMap,
        array &$priceListDupMap,
        array &$productMap,
        array &$productDupMap
    ): void {
        $messages = $buffer->getMessagesForTopics($this->topics);
        foreach ($messages as $messageId => [$topic, $message]) {
            foreach ($message[self::PRODUCT] as $priceListId => $productIds) {
                if ($productIds) {
                    foreach ($productIds as $productId) {
                        if (isset($productMap[$topic][$priceListId][$productId])) {
                            $productDupMap[$topic][$priceListId][$productId][] = $messageId;
                        }
                        $productMap[$topic][$priceListId][$productId][] = $messageId;
                    }
                } else {
                    if (isset($priceListMap[$topic][$priceListId])) {
                        $priceListDupMap[$topic][$priceListId][] = $messageId;
                    }
                    $priceListMap[$topic][$priceListId][] = $messageId;
                }
            }
        }
    }

    /**
     * Removes messages by price list + products if a message by the corresponding price list exists.
     *
     * @param MessageBuffer $buffer
     * @param array $priceListMap [topic => [priceListId => [messageId, ...], ...], ...]
     * @param array $productMap [topic => [priceListId => [productId => [messageId, ...], ...], ...], ...]
     * @param array $productDupMap [topic => [priceListId => [productId => [messageId, ...], ...], ...], ...]
     */
    private function removeRedundantProductMessages(
        MessageBuffer $buffer,
        array $priceListMap,
        array &$productMap,
        array &$productDupMap
    ): void {
        foreach ($productMap as $topic => $priceLists) {
            foreach ($priceLists as $priceListId => $products) {
                if (!isset($priceListMap[$topic][$priceListId])) {
                    continue;
                }
                foreach ($products as $productId => $messageIds) {
                    foreach ($messageIds as $messageId) {
                        $message = $buffer->getMessage($messageId);
                        if (null === $message || !isset($message[self::PRODUCT][$priceListId])) {
                            continue;
                        }
                        if (count($message[self::PRODUCT]) > 1) {
                            unset($message[self::PRODUCT][$priceListId]);
                            $buffer->replaceMessage($messageId, $message);
                        } else {
                            $buffer->removeMessage($messageId);
                        }
                    }
                }
                unset($productMap[$topic][$priceListId], $productDupMap[$topic][$priceListId]);
            }
        }
    }

    /**
     * Removes resolve price rules messages if the corresponding price list assignment messages exist,
     * because assignment calculation will trigger all rules rebuild.
     */
    private function removeRedundantMessagesForResolvePriceRules(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopic(ResolvePriceRulesTopic::getName())) {
            return;
        }
        if (!$buffer->hasMessagesForTopic(ResolvePriceListAssignedProductsTopic::getName())) {
            return;
        }

        $assignPriceListIds = $this->collectAssignPriceListIds($buffer);
        $messages = $buffer->getMessagesForTopic(ResolvePriceRulesTopic::getName());
        foreach ($messages as $messageId => $message) {
            $hasChanges = false;
            foreach ($message[self::PRODUCT] as $priceListId => $products) {
                if (empty($products) && isset($assignPriceListIds[$priceListId])) {
                    unset($message[self::PRODUCT][$priceListId]);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                if (empty($message[self::PRODUCT])) {
                    $buffer->removeMessage($messageId);
                } else {
                    $buffer->replaceMessage($messageId, $message);
                }
            }
        }
    }

    /**
     * @param MessageBuffer $buffer
     *
     * @return array [priceListId => true, ...]
     */
    private function collectAssignPriceListIds(MessageBuffer $buffer): array
    {
        $assignPriceListIds = [];
        $messages = $buffer->getMessagesForTopic(ResolvePriceListAssignedProductsTopic::getName());
        foreach ($messages as $message) {
            foreach ($message[self::PRODUCT] as $priceListId => $products) {
                if (empty($products) && !isset($assignPriceListIds[$priceListId])) {
                    $assignPriceListIds[$priceListId] = true;
                }
            }
        }

        return $assignPriceListIds;
    }

    /**
     * Removes duplicated messages by price list.
     *
     * @param MessageBuffer $buffer
     * @param array $priceListDupMap [topic => [priceListId => [messageId, ...], ...], ...]
     */
    private function removeDuplicatedMessagesForPriceLists(MessageBuffer $buffer, array $priceListDupMap): void
    {
        foreach ($priceListDupMap as $topic => $priceLists) {
            foreach ($priceLists as $priceListId => $messageIds) {
                foreach ($messageIds as $messageId) {
                    /** @var array|null $message */
                    $message = $buffer->getMessage($messageId);
                    if (null === $message) {
                        continue;
                    }
                    if (!isset($message[self::PRODUCT][$priceListId])) {
                        continue;
                    }
                    unset($message[self::PRODUCT][$priceListId]);
                    if (empty($message[self::PRODUCT])) {
                        $buffer->removeMessage($messageId);
                    } else {
                        $buffer->replaceMessage($messageId, $message);
                    }
                }
            }
        }
    }

    /**
     * Removes duplicated messages by price list + products.
     *
     * @param MessageBuffer $buffer
     * @param array $productDupMap [topic => [priceListId => [productId => [messageId, ...], ...], ...], ...]
     */
    private function removeDuplicatedMessagesForProducts(MessageBuffer $buffer, array $productDupMap): void
    {
        foreach ($productDupMap as $topic => $priceLists) {
            foreach ($priceLists as $priceListId => $products) {
                foreach ($products as $productId => $messageIds) {
                    foreach ($messageIds as $messageId) {
                        /** @var array|null $message */
                        $message = $buffer->getMessage($messageId);
                        if (null === $message) {
                            continue;
                        }
                        if (!isset($message[self::PRODUCT][$priceListId])) {
                            continue;
                        }
                        $productIndex = array_search($productId, $message[self::PRODUCT][$priceListId], true);
                        unset($message[self::PRODUCT][$priceListId][$productIndex]);
                        if (empty($message[self::PRODUCT][$priceListId])) {
                            unset($message[self::PRODUCT][$priceListId]);
                        } else {
                            $message[self::PRODUCT][$priceListId] = array_values($message[self::PRODUCT][$priceListId]);
                        }
                        if (empty($message[self::PRODUCT])) {
                            $buffer->removeMessage($messageId);
                        } else {
                            $buffer->replaceMessage($messageId, $message);
                        }
                    }
                }
            }
        }
    }

    /**
     * Splits messages contain items for price list and items for price list + products into different messages.
     */
    private function splitMixedMessages(MessageBuffer $buffer): void
    {
        $mixedMessages = [];
        $messages = $buffer->getMessagesForTopics($this->topics);
        foreach ($messages as $messageId => [$topic, $message]) {
            $hasProductsFlag = null;
            foreach ($message[self::PRODUCT] as $priceListId => $products) {
                $hasProducts = !empty($products);
                if (null === $hasProductsFlag) {
                    $hasProductsFlag = $hasProducts;
                } elseif ($hasProducts !== $hasProductsFlag) {
                    $mixedMessages[$topic][$messageId] = $message;
                    break;
                }
            }
        }
        foreach ($mixedMessages as $topic => $messages) {
            foreach ($messages as $messageId => $message) {
                $this->splitMixedMessage($buffer, $topic, $messageId, $message);
            }
        }
    }

    /**
     * Splits a message contain items for price list and items for price list + products into different messages.
     */
    private function splitMixedMessage(MessageBuffer $buffer, string $topic, int $messageId, array $message): void
    {
        $hasProductsFlag = null;
        $newMessage = [];
        foreach ($message[self::PRODUCT] as $priceListId => $products) {
            $hasProducts = !empty($products);
            if (null === $hasProductsFlag) {
                $hasProductsFlag = $hasProducts;
            } elseif ($hasProducts !== $hasProductsFlag) {
                unset($message[self::PRODUCT][$priceListId]);
                $newMessage[self::PRODUCT][$priceListId] = $products;
            }
        }
        $buffer->replaceMessage($messageId, $message);
        $buffer->addMessage($topic, $newMessage);
    }

    /**
     * Collapses messages from the same topic into one message.
     */
    private function collapseMessages(MessageBuffer $buffer): void
    {
        $firstMessageIdsForPriceList = [];
        $firstMessageIdsForProducts = [];
        $messages = $buffer->getMessagesForTopics($this->topics);
        foreach ($messages as $messageId => [$topic, $message]) {
            $firstMessageIds = &$firstMessageIdsForPriceList;
            if ($this->hasProducts($message)) {
                $firstMessageIds = &$firstMessageIdsForProducts;
            }
            if (isset($firstMessageIds[$topic])) {
                $firstMessageId = $firstMessageIds[$topic];
                /** @var array $firstMessage */
                $firstMessage = $buffer->getMessage($firstMessageId);
                foreach ($message[self::PRODUCT] as $priceListId => $products) {
                    if (!isset($firstMessage[self::PRODUCT][$priceListId])) {
                        $firstMessage[self::PRODUCT][$priceListId] = $products;
                    } elseif (!empty($products)) {
                        $firstMessage[self::PRODUCT][$priceListId] = array_merge(
                            $firstMessage[self::PRODUCT][$priceListId],
                            $products
                        );
                    }
                }
                $buffer->replaceMessage($firstMessageId, $firstMessage);
                $buffer->removeMessage($messageId);
            } else {
                $firstMessageIds[$topic] = $messageId;
            }
        }
    }

    private function hasProducts(array $message): bool
    {
        $firstKey = key($message[self::PRODUCT]);

        return null !== $firstKey && !empty($message[self::PRODUCT][$firstKey]);
    }
}
