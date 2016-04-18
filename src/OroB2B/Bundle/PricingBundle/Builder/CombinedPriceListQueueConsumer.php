<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListChangeTriggerRepository;

class CombinedPriceListQueueConsumer
{
    const MODE_REAL_TIME = 'real_time';
    const MODE_SCHEDULED = 'scheduled';

    /** @var ObjectManager */
    protected $manager;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var CombinedPriceListsBuilder */
    protected $commonPriceListsBuilder;

    /** @var WebsiteCombinedPriceListsBuilder */
    protected $websitePriceListsBuilder;

    /** @var AccountGroupCombinedPriceListsBuilder */
    protected $accountGroupPriceListsBuilder;

    /** @var AccountCombinedPriceListsBuilder */
    protected $accountPriceListsBuilder;

    /** @var  PriceListChangeTriggerRepository */
    protected $queueRepository;

    /**
     * @param ManagerRegistry $registry
     * @param CombinedPriceListsBuilder $commonPriceListsBuilder
     * @param WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder
     * @param AccountGroupCombinedPriceListsBuilder $accountGroupPriceListsBuilder
     * @param AccountCombinedPriceListsBuilder $accountPriceListsBuilder
     */
    public function __construct(
        ManagerRegistry $registry,
        CombinedPriceListsBuilder $commonPriceListsBuilder,
        WebsiteCombinedPriceListsBuilder $websitePriceListsBuilder,
        AccountGroupCombinedPriceListsBuilder $accountGroupPriceListsBuilder,
        AccountCombinedPriceListsBuilder $accountPriceListsBuilder
    ) {
        $this->registry = $registry;
        $this->commonPriceListsBuilder = $commonPriceListsBuilder;
        $this->websitePriceListsBuilder = $websitePriceListsBuilder;
        $this->accountGroupPriceListsBuilder = $accountGroupPriceListsBuilder;
        $this->accountPriceListsBuilder = $accountPriceListsBuilder;
    }

    /**
     * @param int|null $behavior
     */
    public function process($behavior = null)
    {
        $manager = $this->getManager();
        $i = 0;
        foreach ($this->getUniqueTriggersIterator() as $changeItem) {
            $this->handlePriceListChangeTrigger($changeItem, $behavior);
            $manager->remove($changeItem);
            if (++$i % 100 === 0) {
                $manager->flush();
            }
        }
        $manager->flush();
    }

    /**
     * @return BufferedQueryResultIterator|PriceListChangeTrigger[]
     */
    protected function getUniqueTriggersIterator()
    {
        return $this->getRepository()->getPriceListChangeTriggersIterator();
    }

    /**
     * @param PriceListChangeTrigger $trigger
     * @param null $behavior
     */
    protected function handlePriceListChangeTrigger(PriceListChangeTrigger $trigger, $behavior)
    {
        switch (true) {
            case !is_null($trigger->getAccount()):
                $this->accountPriceListsBuilder->build($trigger->getWebsite(), $trigger->getAccount(), $behavior);
                break;
            case !is_null($trigger->getAccountGroup()):
                $this->accountGroupPriceListsBuilder->build(
                    $trigger->getWebsite(),
                    $trigger->getAccountGroup(),
                    $behavior
                );
                break;
            case !is_null($trigger->getWebsite()):
                $this->websitePriceListsBuilder->build($trigger->getWebsite(), $behavior);
                break;
            default:
                $this->commonPriceListsBuilder->build($behavior);
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry
                ->getManagerForClass('OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger');
        }

        return $this->manager;
    }

    /**
     * @return PriceListChangeTriggerRepository
     */
    protected function getRepository()
    {
        if (!$this->queueRepository) {
            $this->queueRepository = $this->getManager()
                ->getRepository('OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger');
        }

        return $this->queueRepository;
    }
}
