<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CustomerGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
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
     * @var CombinedPriceListsBuilderFacade
     */
    protected $combinedPriceListsBuilderFacade;

    /**
     * @var CombinedPriceListsBuilder
     * @deprecated Will be removed in 2.0
     */
    protected $commonPriceListsBuilder;

    /**
     * @var WebsiteCombinedPriceListsBuilder
     * @deprecated Will be removed in 2.0
     */
    protected $websitePriceListsBuilder;

    /**
     * @var CustomerGroupCombinedPriceListsBuilder
     * @deprecated Will be removed in 2.0
     */
    protected $customerGroupPriceListsBuilder;

    /**
     * @var CustomerCombinedPriceListsBuilder
     * @deprecated Will be removed in 2.0
     */
    protected $customerPriceListsBuilder;

    /**
     * @var EventDispatcherInterface
     * @deprecated Will be removed in 2.0
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
     * @var CombinedPriceListTriggerHandler
     */
    private $triggerHandler;

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
     * @param CombinedPriceListTriggerHandler $triggerHandler
     * @deprecated Will be removed in 2.0
     * Dependencies will be injected via constructor
     */
    public function setCombinedPriceListTriggerHandler(CombinedPriceListTriggerHandler $triggerHandler)
    {
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param CombinedPriceListsBuilderFacade $builderFacade
     * @deprecated Will be removed in 2.0
     * Dependencies will be injected via constructor
     */
    public function setCombinedPriceListBuilderFacade(CombinedPriceListsBuilderFacade $builderFacade)
    {
        $this->combinedPriceListsBuilderFacade = $builderFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CombinedPriceList::class);
        $em->beginTransaction();
        $this->triggerHandler->startCollect();
        
        try {
            $messageData = JSON::decode($message->getBody());
            $trigger = $this->triggerFactory->createFromArray($messageData);
            $this->handlePriceListRelationTrigger($trigger);
            $this->dispatchChangeAssociationEvents();
            $this->triggerHandler->commit();
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $this->triggerHandler->rollback();
            $em->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $this->triggerHandler->rollback();
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
                $this->combinedPriceListsBuilderFacade->rebuildForCustomers(
                    [$trigger->getCustomer()],
                    $trigger->getWebsite(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getCustomerGroup()):
                $this->combinedPriceListsBuilderFacade->rebuildForCustomerGroups(
                    [$trigger->getCustomerGroup()],
                    $trigger->getWebsite(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getWebsite()):
                $this->combinedPriceListsBuilderFacade->rebuildForWebsites(
                    [$trigger->getWebsite()],
                    $trigger->isForce()
                );
                break;
            default:
                $this->combinedPriceListsBuilderFacade->rebuildAll($trigger->isForce());
        }
    }

    /**
     * @deprecated Will be removed in 2.0
     * Call $this->combinedPriceListsBuilderFacade->dispatchEvents() directly instead
     */
    protected function dispatchChangeAssociationEvents()
    {
        $this->combinedPriceListsBuilderFacade->dispatchEvents();
    }

    /**
     * @deprecated Will be removed in 2.0
     * Call $this->combinedPriceListsBuilderFacade->dispatchEvents() directly instead
     */
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

    /**
     * @deprecated Will be removed in 2.0
     * Call $this->combinedPriceListsBuilderFacade->dispatchEvents() directly instead
     */
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

    /**
     * @deprecated Will be removed in 2.0
     * Call $this->combinedPriceListsBuilderFacade->dispatchEvents() directly instead
     */
    protected function dispatchWebsiteScopeEvent()
    {
        $websiteBuildList = $this->websitePriceListsBuilder->getBuiltList();
        if ($websiteBuildList) {
            $event = new WebsiteCPLUpdateEvent(array_filter(array_keys($websiteBuildList)));
            $this->dispatcher->dispatch(WebsiteCPLUpdateEvent::NAME, $event);
        }
    }

    /**
     * @deprecated Will be removed in 2.0
     * Call $this->combinedPriceListsBuilderFacade->dispatchEvents() directly instead
     */
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

    /**
     * @deprecated Will be removed in 2.0
     * $this->combinedPriceListsBuilderFacade now properly handles cache reset by itself
     */
    protected function resetCache()
    {
    }
}
