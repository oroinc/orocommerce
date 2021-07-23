<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Refreshes price lists that contain schedules.
 */
class UpdatePriceListsContainSchedule implements ProcessorInterface
{
    /** data structure: [price list id => price list, ...] */
    public const PRICE_LISTS = 'price_lists_to_update_contain_schedule';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        /** @var PriceList $priceList */
        $priceLists = $context->get(self::PRICE_LISTS);
        foreach ($priceLists as $priceList) {
            $priceList->refreshContainSchedule();
        }
        $this->doctrineHelper->getEntityManagerForClass(PriceList::class)->flush();
        $context->remove(self::PRICE_LISTS);
    }
}
