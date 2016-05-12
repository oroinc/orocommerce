<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider;

class FreightClassSelectType extends AbstractShippingOptionSelectType
{
    const NAME = 'orob2b_shipping_freight_class_select';

    /** @var FreightClassesProvider */
    protected $unitProvider;

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $formParent = $form->getParent();
        if (!$options['full_list'] && $formParent) {
            $choices = $this->unitProvider->getFreightClasses($formParent->getData(), $options['compact']);

            $view->vars['choices'] = [];
            foreach ($choices as $choice) {
                $view->vars['choices'][] = new ChoiceView($choice, $choice->getCode(), $choice->getCode());
            }
        }

        parent::finishView($view, $form, $options);
    }
}
