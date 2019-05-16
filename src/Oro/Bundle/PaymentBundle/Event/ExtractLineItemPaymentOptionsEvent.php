<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event which should be used to create LineItemOptionModel items which represent line item data in a payment systen
 */
class ExtractLineItemPaymentOptionsEvent extends Event
{
    const NAME = 'oro_payment.event.extract_line_item_options';

    /** @var LineItemsAwareInterface */
    protected $entity;

    /** @var LineItemOptionModel[] */
    protected $models = [];

    /** @var array */
    protected $context = [];

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

    /**
     * @param LineItemOptionModel $model
     */
    public function addModel(LineItemOptionModel $model)
    {
        if (!in_array($model, $this->models, true)) {
            $this->models[] = $model;
        }
    }

    /**
     * Set additional context data which can be used by listeners
     *
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get additional context data which can be used by listeners
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
