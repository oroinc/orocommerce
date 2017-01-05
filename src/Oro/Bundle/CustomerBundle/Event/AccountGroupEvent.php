<?php

namespace Oro\Bundle\CustomerBundle\Event;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class AccountGroupEvent extends AfterFormProcessEvent
{
    const PRE_REMOVE = 'oro_customer.account_group.pre_remove';
    const BEFORE_FLUSH = 'oro_customer.account_group.before_flush';

    /**
     * @param CustomerGroup $accountGroup
     * @param FormInterface $form
     */
    public function __construct(CustomerGroup $accountGroup, FormInterface $form = null)
    {
        $this->data = $accountGroup;
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
