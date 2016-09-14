<?php

namespace Oro\Bundle\AccountBundle\Event;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupEvent extends AfterFormProcessEvent
{
    const PRE_REMOVE = 'oro_account.account_group.pre_remove';
    const BEFORE_FLUSH = 'oro_account.account_group.before_flush';

    /**
     * @param AccountGroup $accountGroup
     * @param FormInterface $form
     */
    public function __construct(AccountGroup $accountGroup, FormInterface $form = null)
    {
        $this->data = $accountGroup;
        $this->form = $form;
    }

    /**
     * @return AccountGroup
     */
    public function getData()
    {
        return $this->data;
    }
}
