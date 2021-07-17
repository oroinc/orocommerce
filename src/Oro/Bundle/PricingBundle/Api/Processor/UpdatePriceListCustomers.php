<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates customers for price lists.
 */
class UpdatePriceListCustomers implements ProcessorInterface
{
    /** data structure: [customer id => [website id => [customer, website], ...], ...] */
    public const CUSTOMERS = 'price_list_customers_to_update';

    /** @var PriceListRelationTriggerHandler */
    private $relationChangesHandler;

    public function __construct(PriceListRelationTriggerHandler $relationChangesHandler)
    {
        $this->relationChangesHandler = $relationChangesHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $customers = $context->get(self::CUSTOMERS);
        foreach ($customers as $items) {
            foreach ($items as list($customer, $website)) {
                $this->relationChangesHandler->handleCustomerChange($customer, $website);
            }
        }
        $context->remove(self::CUSTOMERS);
    }
}
