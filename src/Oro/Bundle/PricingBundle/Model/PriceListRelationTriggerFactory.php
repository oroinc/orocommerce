<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
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
            $customer = $this->registry->getRepository(Customer::class)
                ->find($data[PriceListRelationTrigger::ACCOUNT]);
            if (!$customer) {
                throw new InvalidArgumentException('Customer was not found');
            }
            $priceListChangeTrigger->setCustomer($customer);
        }
        if ($data[PriceListRelationTrigger::ACCOUNT_GROUP]) {
            $customerGroup = $this->registry->getRepository(CustomerGroup::class)
                ->find($data[PriceListRelationTrigger::ACCOUNT_GROUP]);
            if (!$customerGroup) {
                throw new InvalidArgumentException('Customer was not found');
            }
            $priceListChangeTrigger->setCustomerGroup($customerGroup);
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
