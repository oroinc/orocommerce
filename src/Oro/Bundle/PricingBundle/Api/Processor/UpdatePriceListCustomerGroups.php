<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates customer groups for price lists.
 */
class UpdatePriceListCustomerGroups implements ProcessorInterface
{
    /** data structure: [customer group id => [website id => [customer group, website], ...], ...] */
    public const CUSTOMER_GROUPS = 'price_list_customer_groups_to_update';

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

        $customerGroups = $context->get(self::CUSTOMER_GROUPS);
        foreach ($customerGroups as $items) {
            foreach ($items as list($customerGroup, $website)) {
                $this->relationChangesHandler->handleCustomerGroupChange($customerGroup, $website);
            }
        }
        $context->remove(self::CUSTOMER_GROUPS);
    }
}
