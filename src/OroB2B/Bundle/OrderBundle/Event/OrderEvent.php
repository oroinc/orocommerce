<?php

namespace Oro\Bundle\OrderBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\OrderBundle\Entity\Order;

class OrderEvent extends Event
{
    const NAME = 'orob2b_order.order';

    /** @var FormInterface */
    protected $form;

    /** @var Order */
    protected $order;

    /** @var \ArrayObject */
    protected $data;

    /** @var array */
    protected $submittedData = [];

    /**
     * @param FormInterface $form
     * @param Order $order
     * @param array $submittedData
     */
    public function __construct(FormInterface $form, Order $order, array $submittedData = [])
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

    /**
     * @return array
     */
    public function getSubmittedData()
    {
        return $this->submittedData;
    }
}
