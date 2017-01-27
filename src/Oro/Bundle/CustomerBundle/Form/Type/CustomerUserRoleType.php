<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class CustomerUserRoleType extends AbstractCustomerUserRoleType
{
    const NAME = 'oro_customer_customer_user_role';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add(
            'customer',
            CustomerSelectType::NAME,
            [
                'required' => false,
                'label' => 'oro.customer.customeruserrole.customer.label'
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
}
