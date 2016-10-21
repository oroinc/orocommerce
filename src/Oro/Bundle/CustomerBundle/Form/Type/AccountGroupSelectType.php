<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class AccountGroupSelectType extends AbstractType
{
    const NAME = 'oro_customer_account_group_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_account_group',
                'create_form_route' => 'oro_customer_account_group_create',
                'configs' => [
                    'placeholder' => 'oro.customer.accountgroup.form.choose'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }
}
