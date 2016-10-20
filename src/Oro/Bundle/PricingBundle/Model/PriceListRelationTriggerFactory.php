<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
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
            if (!$account) {
                throw new InvalidArgumentException('Account was not found');
            }
            $priceListChangeTrigger->setAccount($account);
        }
        if ($data[PriceListRelationTrigger::ACCOUNT_GROUP]) {
            $accountGroup = $this->registry->getRepository(AccountGroup::class)
                ->find($data[PriceListRelationTrigger::ACCOUNT_GROUP]);
            if (!$accountGroup) {
                throw new InvalidArgumentException('Account was not found');
            }
            $priceListChangeTrigger->setAccountGroup($accountGroup);
        }
        if ($data[PriceListRelationTrigger::WEBSITE]) {
            $website = $this->registry->getRepository(Website::class)
                ->find($data[PriceListRelationTrigger::WEBSITE]);
            if (!$website) {
                throw new InvalidArgumentException('Website was not found');
            }
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
