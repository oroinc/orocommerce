<?php

namespace Oro\Bundle\CustomerBundle\Event;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class CustomerGroupEvent extends AfterFormProcessEvent
{
    const PRE_REMOVE = 'oro_customer.customer_group.pre_remove';
    const BEFORE_FLUSH = 'oro_customer.customer_group.before_flush';

    /**
     * @param CustomerGroup $customerGroup
     * @param FormInterface $form
     */
    public function __construct(CustomerGroup $customerGroup, FormInterface $form = null)
    {
        $this->data = $customerGroup;
        $this->form = $form;
    }

    /**
     * @return CustomerGroup
     */
    public function getData()
    {
        return $this->data;
    }
}
