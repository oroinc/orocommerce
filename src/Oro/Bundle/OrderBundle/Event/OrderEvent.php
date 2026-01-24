<?php

namespace Oro\Bundle\OrderBundle\Event;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched during order form processing and manipulation.
 *
 * Carries order, form, and submitted data information to event listeners, allowing them to react
 * to order-related events and modify order data during form submission and processing workflows.
 */
class OrderEvent extends Event implements EntityDataAwareEventInterface
{
    const NAME = 'oro_order.order';

    /** @var FormInterface */
    protected $form;

    /** @var Order */
    protected $order;

    /** @var \ArrayObject */
    protected $data;

    /** @var array|null */
    protected $submittedData;

    public function __construct(FormInterface $form, Order $order, ?array $submittedData = null)
    {
        $this->form = $form;
        $this->order = $order;

        $this->data = new \ArrayObject();
        $this->submittedData = $submittedData;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    #[\Override]
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    #[\Override]
    public function getSubmittedData()
    {
        return $this->submittedData;
    }

    /**
     * @return Order
     */
    #[\Override]
    public function getEntity()
    {
        return $this->getOrder();
    }
}
