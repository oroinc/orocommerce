<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class ExtractLineItemPaymentOptionsEvent extends Event
{
    const NAME = 'oro_payment.event.extract_line_item_options';

    /** @var LineItemsAwareInterface */
    protected $entity;

    /** @var LineItemOptionModel[] */
    protected $models = [];

    /**
     * @param LineItemsAwareInterface $entity
     */
    public function __construct(LineItemsAwareInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return LineItemsAwareInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return LineItemOptionModel[]
     */
    public function getModels()
    {
        return $this->models;
    }

    public function addModel(LineItemOptionModel $model)
    {
        if (!in_array($model, $this->models, true)) {
            $this->models[] = $model;
        }
    }
}
