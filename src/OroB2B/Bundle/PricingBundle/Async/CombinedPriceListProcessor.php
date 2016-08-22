<?php

namespace OroB2B\Bundle\PricingBundle\Async;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;

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
     * @var PriceListChangeTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CombinedPriceListsBuilder $commonPriceListsBuilder
     * @param WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder
     * @param AccountGroupCombinedPriceListsBuilder $accountGroupPriceListsBuilder
     * @param AccountCombinedPriceListsBuilder $accountPriceListsBuilder
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param PriceListChangeTriggerFactory $triggerFactory
     */
    public function __construct(
        CombinedPriceListsBuilder $commonPriceListsBuilder,
        WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder,
        AccountGroupCombinedPriceListsBuilder $accountGroupPriceListsBuilder,
        AccountCombinedPriceListsBuilder $accountPriceListsBuilder,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        PriceListChangeTriggerFactory $triggerFactory
    ) {
        $this->commonPriceListsBuilder = $commonPriceListsBuilder;
        $this->websitePriceListsBuilder = $websitePriceListsBuilder;
        $this->accountGroupPriceListsBuilder = $accountGroupPriceListsBuilder;
        $this->accountPriceListsBuilder = $accountPriceListsBuilder;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->triggerFactory = $triggerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $this->logger->debug('orob2b_pricing.async.combined_price_list_processor ' . $message->getMessageId());
            $this->resetCache();
            $trigger = $this->triggerFactory->createFromMessage($message);
            $this->handlePriceListChangeTrigger($trigger);
            $this->dispatchChangeAssociationEvents();
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @param PriceListChangeTrigger $trigger
     */
    protected function handlePriceListChangeTrigger(PriceListChangeTrigger $trigger)
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
        return [Topics::REBUILD_PRICE_LISTS];
    }

    protected function resetCache()
    {
        $this->commonPriceListsBuilder->resetCache();
        $this->websitePriceListsBuilder->resetCache();
        $this->accountGroupPriceListsBuilder->resetCache();
        $this->accountPriceListsBuilder->resetCache();
    }
}
