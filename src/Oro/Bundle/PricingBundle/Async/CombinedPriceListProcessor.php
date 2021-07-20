<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates combined price lists in case of changes in structure of original price lists.
 */
class CombinedPriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    /** @var CombinedPriceListTriggerHandler */
    private $triggerHandler;

    /** @var CombinedPriceListsBuilderFacade */
    private $builderFacade;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        CombinedPriceListTriggerHandler $triggerHandler,
        CombinedPriceListsBuilderFacade $builderFacade
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->triggerHandler = $triggerHandler;
        $this->builderFacade = $builderFacade;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REBUILD_COMBINED_PRICE_LISTS];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!\is_array($body)) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $this->triggerHandler->startCollect();
        try {
            $this->handlePriceListRelationTrigger($body);
            $this->builderFacade->dispatchEvents();
            $this->triggerHandler->commit();
        } catch (InvalidArgumentException $e) {
            $this->triggerHandler->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $this->triggerHandler->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    private function handlePriceListRelationTrigger(array $body): void
    {
        $force = $body['force'] ?? false;
        $customer = $this->findCustomer($body);
        if (null !== $customer) {
            $this->builderFacade->rebuildForCustomers([$customer], $this->findWebsite($body), $force);
        } else {
            $customerGroup = $this->findCustomerGroup($body);
            $website = $this->findWebsite($body);
            if (null !== $customerGroup) {
                $this->builderFacade->rebuildForCustomerGroups([$customerGroup], $website, $force);
            } elseif (null !== $website) {
                $this->builderFacade->rebuildForWebsites([$website], $force);
            } else {
                $this->builderFacade->rebuildAll($force);
            }
        }
    }

    private function findCustomer(array $body): ?Customer
    {
        if (!isset($body['customer'])) {
            return null;
        }

        /** @var Customer|null $customer */
        $customer = $this->doctrine->getManagerForClass(Customer::class)
            ->find(Customer::class, $body['customer']);
        if (null === $customer) {
            throw new InvalidArgumentException('Customer was not found.');
        }

        return $customer;
    }

    private function findCustomerGroup(array $body): ?CustomerGroup
    {
        if (!isset($body['customerGroup'])) {
            return null;
        }

        /** @var CustomerGroup|null $customerGroup */
        $customerGroup = $this->doctrine->getManagerForClass(CustomerGroup::class)
            ->find(CustomerGroup::class, $body['customerGroup']);
        if (null === $customerGroup) {
            throw new InvalidArgumentException('Customer Group was not found.');
        }

        return $customerGroup;
    }

    private function findWebsite(array $body): ?Website
    {
        if (!isset($body['website'])) {
            return null;
        }

        /** @var Website|null $website */
        $website = $this->doctrine->getManagerForClass(Website::class)
            ->find(Website::class, $body['website']);
        if (null === $website) {
            throw new InvalidArgumentException('Website was not found.');
        }

        return $website;
    }
}
