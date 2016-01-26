<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedPriceListCollectionRepository;

class CombinedPriceListQueueConsumer
{
    const MODE_REAL_TIME = 'real_time';
    const MODE_SCHEDULED = 'scheduled';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
        $this->queueRepository = $this->registry
            ->getManagerForClass('OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection')
            ->getRepository('OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection');
    }

    public function process()
    {
        //TODO: delete duplicate jobs
        foreach ($this->getUniqueChangesIterator() as $changes) {
            $this->handleCollectionsJob($changes);
        }
    }

    protected function getUniqueChangesIterator()
    {
        return $this->queueRepository->getCollectionChangesIterator();
    }

    protected function handleCollectionsJob(ChangedPriceListCollection $changes)
    {
        switch (true) {
            case !is_null($changes->getAccount()):
                $this->accountPriceListsBuilder->build($changes->getWebsite(), $changes->getAccount());
                break;
            case !is_null($changes->getAccountGroup()):
                $this->accountGroupPriceListsBuilder->build($changes->getWebsite(), $changes->getAccountGroup());
                break;
            case !is_null($changes->getWebsite()):
                $this->websitePriceListsBuilder->build($changes->getWebsite());
                break;
            default:
                $this->commonPriceListsBuilder->build();
        }
    }
}
