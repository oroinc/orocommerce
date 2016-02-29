<?php

namespace OroB2B\Bundle\PricingBundle\Event;

//todo used only in one place REMOVE
class PriceListQueueMultiChangeEvent extends AbstractPriceListQueueChangeEvent
{
    const NAME = 'orob2b_pricing.price_list_collection.multi_change';
}
