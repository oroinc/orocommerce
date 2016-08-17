<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
     * @param array $data
     * @return PriceListChangeTrigger
     */
    public function create(array $data = [])
    {
        $data = $this->normalizeArrayData($data);

        $priceListChangeTrigger = new PriceListChangeTrigger();
        $priceListChangeTrigger->setAccount($data[PriceListChangeTrigger::ACCOUNT])
            ->setAccountGroup($data[PriceListChangeTrigger::ACCOUNT_GROUP])
            ->setWebsite($data[PriceListChangeTrigger::WEBSITE])
            ->setForce($data[PriceListChangeTrigger::FORCE]);

        return $priceListChangeTrigger;
    }

    /**
     * @param MessageInterface $message
     * @return PriceListChangeTrigger
     */
    public function createFromMessage(MessageInterface $message)
    {
        $data = $message->getBody() ? json_decode($message->getBody(), true) : [];
        $data = $this->normalizeArrayData($data);
        if ($data[PriceListChangeTrigger::ACCOUNT]) {
            $data[PriceListChangeTrigger::ACCOUNT] = $this->registry->getRepository(Account::class)
                ->find($data[PriceListChangeTrigger::ACCOUNT]);
        }
        if ($data[PriceListChangeTrigger::ACCOUNT_GROUP]) {
            $data[PriceListChangeTrigger::ACCOUNT_GROUP] = $this->registry->getRepository(AccountGroup::class)
                ->find($data[PriceListChangeTrigger::ACCOUNT_GROUP]);
        }
        if ($data[PriceListChangeTrigger::WEBSITE]) {
            $data[PriceListChangeTrigger::WEBSITE] = $this->registry->getRepository(Website::class)
                ->find($data[PriceListChangeTrigger::WEBSITE]);
        }

        $priceListChangeTrigger = new PriceListChangeTrigger();

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
