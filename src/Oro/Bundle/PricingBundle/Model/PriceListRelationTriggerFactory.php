<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListRelationTriggerFactory
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return PriceListRelationTrigger
     */
    public function create()
    {
        return new PriceListRelationTrigger();
    }

    /**
     * @param array $data
     * @return PriceListRelationTrigger
     */
    public function createFromArray($data = [])
    {
        $data = $this->normalizeArrayData($data);

        $priceListChangeTrigger = new PriceListRelationTrigger();
        if ($data[PriceListRelationTrigger::ACCOUNT]) {
            $account = $this->registry->getRepository(Account::class)
                ->find($data[PriceListRelationTrigger::ACCOUNT]);
            $priceListChangeTrigger->setAccount($account);
        }
        if ($data[PriceListRelationTrigger::ACCOUNT_GROUP]) {
            $accountGroup = $this->registry->getRepository(AccountGroup::class)
                ->find($data[PriceListRelationTrigger::ACCOUNT_GROUP]);
            $priceListChangeTrigger->setAccountGroup($accountGroup);
        }
        if ($data[PriceListRelationTrigger::WEBSITE]) {
            $website = $this->registry->getRepository(Website::class)
                ->find($data[PriceListRelationTrigger::WEBSITE]);
            $priceListChangeTrigger->setWebsite($website);
        }

        return $priceListChangeTrigger;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function normalizeArrayData(array $data)
    {
        return array_replace(
            [
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::WEBSITE => null,
                PriceListRelationTrigger::FORCE => false,
            ],
            $data
        );
    }
}
