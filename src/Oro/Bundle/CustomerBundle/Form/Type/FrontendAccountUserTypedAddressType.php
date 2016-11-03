<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FrontendAccountUserTypedAddressType extends AccountTypedAddressType
{
    const NAME = 'oro_account_frontend_account_user_typed_address';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var AccountUserAddress $data */
        $data = $event->getData();
        $formOptions = [
            'data' => $data->getFrontendOwner()->getId(),
            'label' => 'oro.customer.accountuser.entity_label'
        ];
        $form->add('frontendOwner', FrontendAccountUserSelectType::NAME, $formOptions);
    }
}
