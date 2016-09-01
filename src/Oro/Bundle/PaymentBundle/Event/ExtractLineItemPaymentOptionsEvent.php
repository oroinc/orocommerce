<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class ExtractLineItemPaymentOptionsEvent extends AbstractExtractOptionsEvent
{
    const NAME = 'oro_payment.event.extract_line_item_options';

    /** @var LineItemsAwareInterface */
    protected $entity;

    /**
     * @param LineItemsAwareInterface $entity
     * @param array $keys
     */
    public function __construct(LineItemsAwareInterface $entity, array $keys)
    {
        $this->entity = $entity;
        $this->keys = $keys;
    }

    /**
     * @return LineItemsAwareInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
