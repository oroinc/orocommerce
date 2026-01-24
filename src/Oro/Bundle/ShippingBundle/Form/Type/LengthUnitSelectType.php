<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

/**
 * Form type for selecting length units.
 *
 * This form type provides a dropdown for selecting length units (e.g., inch, foot, cm, m)
 * used for specifying package dimensions in shipping calculations.
 */
class LengthUnitSelectType extends AbstractShippingOptionSelectType
{
    const NAME = 'oro_shipping_length_unit_select';
}
