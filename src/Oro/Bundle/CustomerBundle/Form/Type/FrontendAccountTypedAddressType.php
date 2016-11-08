<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class FrontendAccountTypedAddressType extends AccountTypedAddressType
{
    const NAME = 'oro_account_frontend_typed_address';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('frontendOwner', FrontendAccountSelectType::NAME, [
            'label' => 'oro.customer.account.entity_label'
        ]);
    }
}
