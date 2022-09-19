<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;

/**
 * The filter for the REBUILD_COMBINED_PRICE_LISTS topic that does the following:
 * * removes duplicated messages
 * * in case the full rebuild message exists, removes all other REBUILD_COMBINED_PRICE_LISTS messages
 * * removes customer messages if the corresponding customer group messages exist,
 *   because in this case customers will be updated in scope of customer groups
 * * removes customer messages if the corresponding website messages exist,
 *   because in this case customers will be updated in scope of websites
 * * removes customer group messages if the corresponding website messages exist,
 *   because in this case customer groups will be updated in scope of websites
 *
 * @see \Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PriceListRelationMessageFilter implements MessageFilterInterface
{
    private const WEBSITE        = 'website';
    private const CUSTOMER       = 'customer';
    private const CUSTOMER_GROUP = 'customerGroup';
    private const FORCE          = 'force';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var array|null */
    private $processedMessages;

    /** @var array|null */
    private $changedWebsites;

    /** @var array|null */
    private $checkCustomerGroupFallback;

    /** @var array|null */
    private $checkCustomerFallback;

    /** @var array|null */
    private $preserveCustomerGroups;

    /** @var array|null */
    private $preserveCustomers;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopic(RebuildCombinedPriceListsTopic::getName())) {
            return;
        }

        try {
            $fullRebuildMessageId = $this->collectData($buffer);
            if (null === $fullRebuildMessageId) {
                $this->removeRedundantMessages($buffer);
            } else {
                $this->removeAllMessagesExceptFullRebuildMessage($buffer, $fullRebuildMessageId);
            }

            $this->reduceMessages($buffer);
        } finally {
            $this->processedMessages = null;
            $this->changedWebsites = null;
            $this->checkCustomerGroupFallback = null;
            $this->checkCustomerFallback = null;
            $this->preserveCustomerGroups = null;
            $this->preserveCustomers = null;
        }
    }

    /**
     * @param MessageBuffer $buffer
     *
     * @return int|null The ID of the first full rebuild message if it is exist in the buffer
     */
    private function collectData(MessageBuffer $buffer): ?int
    {
        $fullRebuildMessageId = null;
        $this->changedWebsites = [];
        $this->checkCustomerGroupFallback = [self::CUSTOMER_GROUP => [], self::WEBSITE => []];
        $this->checkCustomerFallback = [self::CUSTOMER => [], self::WEBSITE => []];
        $this->processedMessages = [];
        $messages = $buffer->getMessagesForTopic(RebuildCombinedPriceListsTopic::getName());
        foreach ($messages as $messageId => $message) {
            if ($this->isFullRebuildMessage($message)) {
                $fullRebuildMessageId = $messageId;
                break;
            }

            $messageKey = $this->getMessageKey($message);
            if (isset($this->processedMessages[$messageKey])) {
                $buffer->removeMessage($messageId);
                continue;
            }

            $this->processedMessages[$messageKey] = true;
            if (isset($message[self::CUSTOMER])) {
                $this->checkCustomerFallback[self::CUSTOMER][$message[self::CUSTOMER]] = true;
                $this->checkCustomerFallback[self::WEBSITE][$message[self::WEBSITE]] = true;
                if (isset($message[self::CUSTOMER_GROUP])) {
                    $this->checkCustomerGroupFallback[self::CUSTOMER_GROUP][$message[self::CUSTOMER_GROUP]] = true;
                    $this->checkCustomerGroupFallback[self::WEBSITE][$message[self::WEBSITE]] = true;
                }
            } elseif (isset($message[self::CUSTOMER_GROUP])) {
                $this->checkCustomerGroupFallback[self::CUSTOMER_GROUP][$message[self::CUSTOMER_GROUP]] = true;
                $this->checkCustomerGroupFallback[self::WEBSITE][$message[self::WEBSITE]] = true;
            } elseif (isset($message[self::WEBSITE])) {
                $this->changedWebsites[$message[self::WEBSITE]] = true;
            }
        }

        return $fullRebuildMessageId;
    }

    private function getMessageKey(array $message): string
    {
        return sprintf(
            '%s_%s_%s_%s',
            $message[self::WEBSITE] ?? null,
            $message[self::CUSTOMER_GROUP] ?? null,
            $message[self::CUSTOMER] ?? null,
            ($message[self::FORCE] ?? false) ? 'f' : ''
        );
    }

    private function isFullRebuildMessage(array $message): bool
    {
        return isset($message[self::FORCE]) && $message[self::FORCE] && count($message) === 1;
    }

    private function removeAllMessagesExceptFullRebuildMessage(MessageBuffer $buffer, int $fullRebuildMessageId): void
    {
        $messages = $buffer->getMessagesForTopic(RebuildCombinedPriceListsTopic::getName());
        foreach ($messages as $messageId => $message) {
            if ($messageId !== $fullRebuildMessageId) {
                $buffer->removeMessage($messageId);
            }
        }
    }

    private function removeRedundantMessages(MessageBuffer $buffer): void
    {
        $messages = $buffer->getMessagesForTopic(RebuildCombinedPriceListsTopic::getName());
        foreach ($messages as $messageId => $message) {
            if (!isset($message[self::WEBSITE])) {
                continue;
            }
            if (isset($message[self::CUSTOMER])) {
                // update customer only if it will be not updated in scope of customer group or website
                if ($this->isRedundantCustomerMessage($message)) {
                    $buffer->removeMessage($messageId);
                }
            } elseif (isset($message[self::CUSTOMER_GROUP])) {
                // update customer group only if it will be not updated in scope of website
                if ($this->isRedundantCustomerGroupMessage($message)) {
                    $buffer->removeMessage($messageId);
                }
            }
        }
    }

    private function reduceMessages(MessageBuffer $buffer): void
    {
        $messages = $buffer->getMessagesForTopic(RebuildCombinedPriceListsTopic::getName());
        $merged = [];
        foreach ($messages as $messageId => $message) {
            $merged[] = $message;
            $buffer->removeMessage($messageId);
        }
        $buffer->addMessage(MassRebuildCombinedPriceListsTopic::getName(), ['assignments' => $merged]);
    }

    private function isRedundantCustomerGroupMessage(array $message): bool
    {
        if (isset($this->changedWebsites[$message[self::WEBSITE]])) {
            return !$this->isPreserveCustomerGroup($message);
        }
        return false;
    }

    private function isRedundantCustomerMessage(array $message): bool
    {
        if (isset($this->changedWebsites[$message[self::WEBSITE]])) {
            return !$this->isPreserveCustomer($message);
        }

        if (!isset($message[self::CUSTOMER_GROUP])) {
            return false;
        }

        $customerGroupMessage = $message;
        unset($customerGroupMessage[self::CUSTOMER]);
        if (!isset($this->processedMessages[$this->getMessageKey($customerGroupMessage)])) {
            return false;
        }

        if ($this->isPreserveCustomer($message)) {
            return false;
        }

        return !$this->isRedundantCustomerGroupMessage($customerGroupMessage);
    }

    private function isPreserveCustomerGroup(array $message): bool
    {
        if (null === $this->preserveCustomerGroups) {
            $this->preserveCustomerGroups = $this->loadPreserveCustomerGroups();
        }

        $preserveCustomerGroupKey = $this->getPreserveCustomerGroupKey(
            $message[self::WEBSITE],
            $message[self::CUSTOMER_GROUP]
        );

        return isset($this->preserveCustomerGroups[$preserveCustomerGroupKey]);
    }

    private function isPreserveCustomer(array $message): bool
    {
        if (null === $this->preserveCustomers) {
            $this->preserveCustomers = $this->loadPreserveCustomers();
        }

        $preserveCustomerKey = $this->getPreserveCustomerKey(
            $message[self::WEBSITE],
            $message[self::CUSTOMER]
        );

        return isset($this->preserveCustomers[$preserveCustomerKey]);
    }

    private function loadPreserveCustomerGroups(): array
    {
        $customerGroups = array_keys($this->checkCustomerGroupFallback[self::CUSTOMER_GROUP]);
        if (!$customerGroups) {
            return [];
        }
        $websites = array_keys($this->checkCustomerGroupFallback[self::WEBSITE]);
        if (!$websites) {
            return [];
        }

        $customerGroupFallbackRepository = $this->doctrine->getRepository(PriceListCustomerGroupFallback::class);
        /** @var PriceListCustomerGroupFallback[] $customerGroupNonDefaultFallback */
        $customerGroupNonDefaultFallback = $customerGroupFallbackRepository->findBy([
            'website'       => $websites,
            'customerGroup' => $customerGroups,
            'fallback'      => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
        ]);
        $preserveCustomerGroups = [];
        foreach ($customerGroupNonDefaultFallback as $fallback) {
            $preserveCustomerGroupKey = $this->getPreserveCustomerGroupKey(
                $fallback->getWebsite()->getId(),
                $fallback->getCustomerGroup()->getId()
            );
            $preserveCustomerGroups[$preserveCustomerGroupKey] = true;
        }

        return $preserveCustomerGroups;
    }

    private function loadPreserveCustomers(): array
    {
        $customers = array_keys($this->checkCustomerFallback[self::CUSTOMER]);
        if (!$customers) {
            return [];
        }
        $websites = array_keys($this->checkCustomerFallback[self::WEBSITE]);
        if (!$websites) {
            return [];
        }

        $customerFallbackRepository = $this->doctrine->getRepository(PriceListCustomerFallback::class);
        /** @var PriceListCustomerFallback[] $customerNonDefaultFallback */
        $customerNonDefaultFallback = $customerFallbackRepository->findBy([
            'website'  => $websites,
            'customer' => $customers,
            'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY
        ]);
        $preserveCustomers = [];
        foreach ($customerNonDefaultFallback as $fallback) {
            $preserveCustomerKey = $this->getPreserveCustomerKey(
                $fallback->getWebsite()->getId(),
                $fallback->getCustomer()->getId()
            );
            $preserveCustomers[$preserveCustomerKey] = true;
        }

        return $preserveCustomers;
    }

    private function getPreserveCustomerGroupKey(int $websiteId, int $customerGroupId): string
    {
        return $websiteId . '_' . $customerGroupId;
    }

    private function getPreserveCustomerKey(int $websiteId, int $customerId): string
    {
        return $websiteId . '_' . $customerId;
    }
}
