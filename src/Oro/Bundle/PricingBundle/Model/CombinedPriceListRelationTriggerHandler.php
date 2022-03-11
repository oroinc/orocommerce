<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\Topic\RebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Provides a set of methods to handle price list collection changes.
 *
 * @see \Oro\Bundle\PricingBundle\Async\PriceListRelationMessageFilter
 */
class CombinedPriceListRelationTriggerHandler implements PriceListRelationTriggerHandlerInterface
{
    private const WEBSITE        = 'website';
    private const CUSTOMER_GROUP = 'customerGroup';
    private const CUSTOMER       = 'customer';
    private const ENTITIES       = ['website', 'customerGroup', 'customer'];

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        MessageProducerInterface $messageProducer,
        ManagerRegistry $doctrine,
        ConfigManager $configManager
    ) {
        $this->messageProducer = $messageProducer;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    public function handleConfigChange(): void
    {
        $this->sendMessage([]);
    }

    public function handleFullRebuild(): void
    {
        $this->sendMessage(['force' => true]);
    }

    public function handleWebsiteChange(Website $website): void
    {
        if (null === $website->getId()) {
            $this->sendMessageWithCallback(function () use ($website) {
                return $this->normalizeMessage([self::WEBSITE => $website->getId()]);
            });
        } else {
            $this->sendMessage([self::WEBSITE => $website->getId()]);
        }
    }

    public function handleCustomerGroupChange(CustomerGroup $customerGroup, Website $website): void
    {
        if (null === $customerGroup->getId()) {
            $this->sendMessageWithCallback(function () use ($customerGroup, $website) {
                return $this->normalizeMessage([
                    self::CUSTOMER_GROUP => $customerGroup->getId(),
                    self::WEBSITE        => $website->getId()
                ]);
            });
        } else {
            $this->sendMessage([
                self::CUSTOMER_GROUP => $customerGroup->getId(),
                self::WEBSITE        => $website->getId()
            ]);
        }
    }

    public function handleCustomerGroupRemove(CustomerGroup $customerGroup): void
    {
        $iterator = $this->doctrine->getRepository(PriceListToCustomer::class)
            ->getCustomerWebsitePairsByCustomerGroupIterator($customerGroup);
        foreach ($iterator as $item) {
            $this->sendMessage($item);
        }
    }

    public function handleCustomerChange(Customer $customer, Website $website): void
    {
        if (null === $customer->getId()) {
            $this->sendMessageWithCallback(function () use ($customer, $website) {
                $message = [self::CUSTOMER => $customer->getId(), self::WEBSITE => $website->getId()];
                $customerGroup = $customer->getGroup();
                if (null !== $customerGroup) {
                    $message[self::CUSTOMER_GROUP] = $customerGroup->getId();
                }

                return $this->normalizeMessage($message);
            });
        } else {
            $message = [self::CUSTOMER => $customer->getId(), self::WEBSITE => $website->getId()];
            $customerGroup = $customer->getGroup();
            if (null !== $customerGroup) {
                $message[self::CUSTOMER_GROUP] = $customerGroup->getId();
            }
            $this->sendMessage($message);
        }
    }

    public function handlePriceListStatusChange(PriceList $priceList): void
    {
        if ($this->isDefaultPriceList($priceList->getId())) {
            $this->handleFullRebuild();
        } else {
            $priceListToCustomerRepository = $this->doctrine->getRepository(PriceListToCustomer::class);
            foreach ($priceListToCustomerRepository->getIteratorByPriceList($priceList) as $item) {
                $this->sendMessage($item);
            }
            $priceListToCustomerGroupRepository = $this->doctrine->getRepository(PriceListToCustomerGroup::class);
            foreach ($priceListToCustomerGroupRepository->getIteratorByPriceList($priceList) as $item) {
                $this->sendMessage($item);
            }
            $priceListToWebsiteRepository = $this->doctrine->getRepository(PriceListToWebsite::class);
            foreach ($priceListToWebsiteRepository->getIteratorByPriceList($priceList) as $item) {
                $this->sendMessage($item);
            }
        }
    }

    private function sendMessage(array $message): void
    {
        $this->messageProducer->send(
            RebuildCombinedPriceListsTopic::getName(),
            $this->normalizeMessage($message)
        );
    }

    private function sendMessageWithCallback(callable $callback): void
    {
        $this->messageProducer->send(RebuildCombinedPriceListsTopic::getName(), new CallbackMessageBuilder($callback));
    }

    private function normalizeMessage(array $message): array
    {
        foreach (self::ENTITIES as $propertyName) {
            if (\array_key_exists($propertyName, $message)) {
                if (null === $message[$propertyName]) {
                    unset($message[$propertyName]);
                } else {
                    $message[$propertyName] = (int)$message[$propertyName];
                }
            }
        }

        return $message;
    }

    private function isDefaultPriceList(int $priceListId): bool
    {
        $defaultPriceListsConfig = $this->configManager->get('oro_pricing.default_price_lists');
        foreach ($defaultPriceListsConfig as $item) {
            if (((int)$item['priceList']) === $priceListId) {
                return true;
            }
        }

        return false;
    }
}
