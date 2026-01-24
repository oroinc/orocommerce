<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for configuring shipping origin in system configuration.
 *
 * This form type extends {@see ShippingOriginType} to handle configuration scope inheritance,
 * propagating the 'use_parent_scope_value' flag to child form fields for proper configuration fallback behavior.
 */
class ShippingOriginConfigType extends AbstractType
{
    const NAME = 'oro_shipping_origin_config';

    #[\Override]
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

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ShippingOriginType::class;
    }
}
