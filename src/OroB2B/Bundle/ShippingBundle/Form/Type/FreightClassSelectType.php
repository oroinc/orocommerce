<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider;

class FreightClassSelectType extends AbstractShippingOptionSelectType
{
    const NAME = 'orob2b_shipping_freight_class_select';

    /** @var FreightClassesProvider */
    protected $unitProvider;

    /**
     * {@ihneritdoc}
     */
    protected function getUnits(FormInterface $form, array $options)
    {
        if ($options['full_list']) {
            return parent::getUnits($form, $options);
        }

        return $this->unitProvider->getFreightClasses($form->getData());
    }
}
