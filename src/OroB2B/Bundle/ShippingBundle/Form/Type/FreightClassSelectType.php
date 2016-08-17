<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;

class FreightClassSelectType extends AbstractShippingOptionSelectType
{
    const NAME = 'orob2b_shipping_freight_class_select';

    /**
     * {@inheritdoc}
     */
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
