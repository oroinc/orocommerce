<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

/**
 * Form type for selecting weight units.
 *
 * This form type provides a dropdown for selecting weight units (e.g., lbs, kg)
 * used for specifying package weight in shipping calculations.
 */
class WeightUnitSelectType extends AbstractShippingOptionSelectType
{
    public const NAME = 'oro_shipping_weight_unit_select';
}
