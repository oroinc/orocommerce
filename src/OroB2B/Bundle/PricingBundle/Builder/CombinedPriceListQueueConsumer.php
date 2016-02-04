<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedPriceListCollectionRepository;

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

    /** @var  ChangedPriceListCollectionRepository */
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
     * @param bool|false $force
     */
    public function process($force = false)
    {
        $manager = $this->getManager();
        $i = 0;
        foreach ($this->getUniqueChangesIterator() as $changeItem) {
            $this->handleCollectionsJob($changeItem, $force);
            $manager->remove($changeItem);
            if (++$i % 100 === 0) {
                $manager->flush();
            }
        }
        $manager->flush();
    }

    /**
     * @return BufferedQueryResultIterator|ChangedPriceListCollection[]
     */
    protected function getUniqueChangesIterator()
    {
        return $this->getRepository()->getCollectionChangesIterator();
    }

    /**
     * @param ChangedPriceListCollection $changeItem
     * @param bool $force
     */
    protected function handleCollectionsJob(ChangedPriceListCollection $changeItem, $force)
    {
        switch (true) {
            case !is_null($changeItem->getAccount()):
                $this->accountPriceListsBuilder->build($changeItem->getWebsite(), $changeItem->getAccount(), $force);
                break;
            case !is_null($changeItem->getAccountGroup()):
                $this->accountGroupPriceListsBuilder->build(
                    $changeItem->getWebsite(),
                    $changeItem->getAccountGroup(),
                    $force
                );
                break;
            case !is_null($changeItem->getWebsite()):
                $this->websitePriceListsBuilder->build($changeItem->getWebsite(), $force);
                break;
            default:
                $this->commonPriceListsBuilder->build($force);
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry
                ->getManagerForClass('OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection');
        }

        return $this->manager;
    }

    /**
     * @return ChangedPriceListCollectionRepository
     */
    protected function getRepository()
    {
        if (!$this->queueRepository) {
            $this->queueRepository = $this->getManager()
                ->getRepository('OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection');
        }

        return $this->queueRepository;
    }
}
