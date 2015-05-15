<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

class ParentCustomerSelectType extends AbstractType
{
    const NAME = 'orob2b_customer_admin_customer_parent_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_customer_admin_customer_parent',
                'configs' => [
                    'extra_config' => 'parent_aware',
                    'placeholder' => 'orob2b.customeradmin.customer.form.choose_parent'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $parentData = $form->getParent()->getData();
        $parentId = null;
        if ($parentData instanceof Customer) {
            $parentId = $parentData->getId();
        }
        $view->vars['parent_id'] = $parentId;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
