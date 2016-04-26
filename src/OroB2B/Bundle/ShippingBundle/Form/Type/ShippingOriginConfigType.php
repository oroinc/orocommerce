<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ShippingOriginConfigType extends AbstractType
{
    const NAME = 'orob2b_shipping_origin_config';

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $parent = $form->getParent();
        if (!$parent) {
            return;
        }

        if (!$parent->has('use_parent_scope_value')) {
            return;
        }

        $useParentScopeValue = $parent->get('use_parent_scope_value')->getData();
        foreach ($view->children as $child) {
            $child->vars['use_parent_scope_value'] = $useParentScopeValue;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ShippingOriginType::NAME;
    }
}
