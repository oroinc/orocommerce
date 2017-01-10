<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CustomerGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerFactory;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CombinedPriceListsBuilder
     */
    protected $commonPriceListsBuilder;

    /**
     * @var WebsiteCombinedPriceListsBuilder
     */
    protected $websitePriceListsBuilder;

    /**
     * @var CustomerGroupCombinedPriceListsBuilder
     */
    protected $customerGroupPriceListsBuilder;

    /**
     * @var CustomerCombinedPriceListsBuilder
     */
    protected $customerPriceListsBuilder;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var PriceListRelationTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DatabaseExceptionHelper
     */
    protected $databaseExceptionHelper;

    /**
     * @param CombinedPriceListsBuilder $commonPriceListsBuilder
     * @param WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder
     * @param CustomerGroupCombinedPriceListsBuilder $customerGroupPriceListsBuilder
     * @param CustomerCombinedPriceListsBuilder $customerPriceListsBuilder
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param PriceListRelationTriggerFactory $triggerFactory
     * @param ManagerRegistry $registry
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function __construct(
        CombinedPriceListsBuilder $commonPriceListsBuilder,
        WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder,
        CustomerGroupCombinedPriceListsBuilder $customerGroupPriceListsBuilder,
        CustomerCombinedPriceListsBuilder $customerPriceListsBuilder,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        PriceListRelationTriggerFactory $triggerFactory,
        ManagerRegistry $registry,
        DatabaseExceptionHelper $databaseExceptionHelper
    ) {
        $this->commonPriceListsBuilder = $commonPriceListsBuilder;
        $this->websitePriceListsBuilder = $websitePriceListsBuilder;
        $this->customerGroupPriceListsBuilder = $customerGroupPriceListsBuilder;
        $this->customerPriceListsBuilder = $customerPriceListsBuilder;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->triggerFactory = $triggerFactory;
        $this->registry = $registry;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CombinedPriceList::class);
        $em->beginTransaction();
        
        try {
            $this->resetCache();
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);
            $this->handlePriceListRelationTrigger($trigger);
            $this->dispatchChangeAssociationEvents();
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Combined Price Lists build',
                ['exception' => $e]
            );

            if ($e instanceof DriverException && $this->databaseExceptionHelper->isDeadlock($e)) {
                return self::REQUEUE;
            } else {
                return self::REJECT;
            }
        }

        return self::ACK;
    }

    /**
     * @param PriceListRelationTrigger $trigger
     */
    protected function handlePriceListRelationTrigger(PriceListRelationTrigger $trigger)
    {
        switch (true) {
            case !is_null($trigger->getCustomer()):
                $this->customerPriceListsBuilder->build(
                    $trigger->getWebsite(),
                    $trigger->getCustomer(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getCustomerGroup()):
                $this->customerGroupPriceListsBuilder->build(
                    $trigger->getWebsite(),
                    $trigger->getCustomerGroup(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getWebsite()):
                $this->websitePriceListsBuilder->build($trigger->getWebsite(), $trigger->isForce());
                break;
            default:
                $this->commonPriceListsBuilder->build($trigger->isForce());
        }
    }

    protected function dispatchChangeAssociationEvents()
    {
        $this->dispatchCustomerScopeEvent();
        $this->dispatchCustomerGroupScopeEvent();
        $this->dispatchWebsiteScopeEvent();
        $this->dispatchConfigScopeEvent();
    }

    protected function dispatchCustomerScopeEvent()
    {
        $customerBuildList = $this->customerPriceListsBuilder->getBuiltList();
        $customerScope = isset($customerBuildList['customer']) ? $customerBuildList['customer'] : null;
        if ($customerScope) {
            $data = [];
            foreach ($customerScope as $websiteId => $customers) {
                $data[] = [
                    'websiteId' => $websiteId,
                    'customers' => array_filter(array_keys($customers)),
                ];
            }
            $event = new CustomerCPLUpdateEvent($data);
            $this->dispatcher->dispatch(CustomerCPLUpdateEvent::NAME, $event);
        }
    }

    protected function dispatchCustomerGroupScopeEvent()
    {
        $customerGroupBuildList = $this->customerGroupPriceListsBuilder->getBuiltList();
        if ($customerGroupBuildList) {
            $data = [];
            foreach ($customerGroupBuildList as $websiteId => $customerGroups) {
                $data[] = [
                    'websiteId' => $websiteId,
                    'customerGroups' => array_filter(array_keys($customerGroups)),
                ];
            }
            $event = new CustomerGroupCPLUpdateEvent($data);
            $this->dispatcher->dispatch(CustomerGroupCPLUpdateEvent::NAME, $event);
        }
    }

    protected function dispatchWebsiteScopeEvent()
    {
        $websiteBuildList = $this->websitePriceListsBuilder->getBuiltList();
        if ($websiteBuildList) {
            $event = new WebsiteCPLUpdateEvent(array_filter(array_keys($websiteBuildList)));
            $this->dispatcher->dispatch(WebsiteCPLUpdateEvent::NAME, $event);
        }
    }

    protected function dispatchConfigScopeEvent()
    {
        if ($this->commonPriceListsBuilder->isBuilt()) {
            $event = new ConfigCPLUpdateEvent();
            $this->dispatcher->dispatch(ConfigCPLUpdateEvent::NAME, $event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REBUILD_COMBINED_PRICE_LISTS];
    }

    protected function resetCache()
    {
        $this->commonPriceListsBuilder->resetCache();
        $this->websitePriceListsBuilder->resetCache();
        $this->customerGroupPriceListsBuilder->resetCache();
        $this->customerPriceListsBuilder->resetCache();
    }
}
