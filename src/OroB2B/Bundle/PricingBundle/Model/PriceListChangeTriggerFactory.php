<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListChangeTriggerFactory
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
     * @return PriceListChangeTrigger
     */
    public function create()
    {
        return new PriceListChangeTrigger();
    }

    /**
     * @param array $data
     * @return PriceListChangeTrigger
     */
    public function createFromArray($data = [])
    {
        $data = $this->normalizeArrayData($data);

        $priceListChangeTrigger = new PriceListChangeTrigger();
        if ($data[PriceListChangeTrigger::ACCOUNT]) {
            $account = $this->registry->getRepository(Account::class)
                ->find($data[PriceListChangeTrigger::ACCOUNT]);
            $priceListChangeTrigger->setAccount($account);
        }
        if ($data[PriceListChangeTrigger::ACCOUNT_GROUP]) {
            $accountGroup = $this->registry->getRepository(AccountGroup::class)
                ->find($data[PriceListChangeTrigger::ACCOUNT_GROUP]);
            $priceListChangeTrigger->setAccountGroup($accountGroup);
        }
        if ($data[PriceListChangeTrigger::WEBSITE]) {
            $website = $this->registry->getRepository(Website::class)
                ->find($data[PriceListChangeTrigger::WEBSITE]);
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
                PriceListChangeTrigger::ACCOUNT => null,
                PriceListChangeTrigger::ACCOUNT_GROUP => null,
                PriceListChangeTrigger::WEBSITE => null,
                PriceListChangeTrigger::FORCE => false,
            ],
            $data
        );
    }
}
