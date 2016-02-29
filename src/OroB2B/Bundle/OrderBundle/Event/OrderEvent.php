<?php

namespace OroB2B\Bundle\OrderBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderEvent extends Event
{
    const NAME = 'orob2b_order.order';

    /** @var FormInterface */
    protected $form;

    /** @var Order */
    protected $order;

    /** @var \ArrayObject */
    protected $data;

    /**
     * @param FormInterface $form
     * @param Order $order
     */
    public function __construct(FormInterface $form, Order $order)
    {
        $this->form = $form;
        $this->order = $order;

        $this->data = new \ArrayObject();
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return \ArrayObject
     */
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
}
