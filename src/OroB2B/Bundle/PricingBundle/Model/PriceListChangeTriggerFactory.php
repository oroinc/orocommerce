<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;

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
    public function create(array $data)
    {
        $data = $this->normalizeArrayData($data);

        $priceListChangeTrigger = new PriceListChangeTrigger();
//        $priceListChangeTrigger

        return $priceListChangeTrigger;
    }

    /**
     * @param MessageInterface $message
     * @return PriceListChangeTrigger
     */
    public function createFromMessage(MessageInterface $message)
    {
        return new PriceListChangeTrigger();
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
