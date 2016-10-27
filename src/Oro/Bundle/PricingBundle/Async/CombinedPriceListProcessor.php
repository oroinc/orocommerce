<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
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
     * @var AccountGroupCombinedPriceListsBuilder
     */
    protected $accountGroupPriceListsBuilder;

    /**
     * @var AccountCombinedPriceListsBuilder
     */
    protected $accountPriceListsBuilder;

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
     * @param AccountGroupCombinedPriceListsBuilder $accountGroupPriceListsBuilder
     * @param AccountCombinedPriceListsBuilder $accountPriceListsBuilder
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param PriceListRelationTriggerFactory $triggerFactory
     * @param ManagerRegistry $registry
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function __construct(
        CombinedPriceListsBuilder $commonPriceListsBuilder,
        WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder,
        AccountGroupCombinedPriceListsBuilder $accountGroupPriceListsBuilder,
        AccountCombinedPriceListsBuilder $accountPriceListsBuilder,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        PriceListRelationTriggerFactory $triggerFactory,
        ManagerRegistry $registry,
        DatabaseExceptionHelper $databaseExceptionHelper
    ) {
        $this->commonPriceListsBuilder = $commonPriceListsBuilder;
        $this->websitePriceListsBuilder = $websitePriceListsBuilder;
        $this->accountGroupPriceListsBuilder = $accountGroupPriceListsBuilder;
        $this->accountPriceListsBuilder = $accountPriceListsBuilder;
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
            case !is_null($trigger->getAccount()):
                $this->accountPriceListsBuilder->build(
                    $trigger->getWebsite(),
                    $trigger->getAccount(),
                    $trigger->isForce()
                );
                break;
            case !is_null($trigger->getAccountGroup()):
                $this->accountGroupPriceListsBuilder->build(
                    $trigger->getWebsite(),
                    $trigger->getAccountGroup(),
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
        $this->dispatchAccountScopeEvent();
        $this->dispatchAccountGroupScopeEvent();
        $this->dispatchWebsiteScopeEvent();
        $this->dispatchConfigScopeEvent();
    }

    protected function dispatchAccountScopeEvent()
    {
        $accountBuildList = $this->accountPriceListsBuilder->getBuiltList();
        $accountScope = isset($accountBuildList['account']) ? $accountBuildList['account'] : null;
        if ($accountScope) {
            $data = [];
            foreach ($accountScope as $websiteId => $accounts) {
                $data[] = [
                    'websiteId' => $websiteId,
                    'accounts' => array_filter(array_keys($accounts)),
                ];
            }
            $event = new AccountCPLUpdateEvent($data);
            $this->dispatcher->dispatch(AccountCPLUpdateEvent::NAME, $event);
        }
    }

    protected function dispatchAccountGroupScopeEvent()
    {
        $accountGroupBuildList = $this->accountGroupPriceListsBuilder->getBuiltList();
        if ($accountGroupBuildList) {
            $data = [];
            foreach ($accountGroupBuildList as $websiteId => $accountGroups) {
                $data[] = [
                    'websiteId' => $websiteId,
                    'accountGroups' => array_filter(array_keys($accountGroups)),
                ];
            }
            $event = new AccountGroupCPLUpdateEvent($data);
            $this->dispatcher->dispatch(AccountGroupCPLUpdateEvent::NAME, $event);
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
        $this->accountGroupPriceListsBuilder->resetCache();
        $this->accountPriceListsBuilder->resetCache();
    }
}
