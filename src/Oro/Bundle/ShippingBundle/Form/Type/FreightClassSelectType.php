<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for selecting freight classes.
 *
 * This form type provides a dropdown for selecting freight classes, with the available options
 * filtered based on the parent form data and applicability rules defined by freight class extensions.
 */
class FreightClassSelectType extends AbstractShippingOptionSelectType
{
    public const NAME = 'oro_shipping_freight_class_select';

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $formParent = $form->getParent();
        if ($formParent && !$options['full_list']) {
            /** @var FreightClass[] $choices */
            $choices = $this->unitProvider->getFreightClasses($formParent->getData(), $options['compact']);

            $view->vars['choices'] = [];

            foreach ($choices as $choice) {
                $view->vars['choices'][] = new ChoiceView($choice, $choice->getCode(), $choice->getCode());
            }
        }

        parent::finishView($view, $form, $options);
    }
}
