Provide Units of Length, Weight and Freight Classes
===================================================

Add migration to load you own weight units, length units and freight classes.
```php
<?php

namespace OroB2B\Bundle\ShippingDemoBundle\Migrations\Data\ORM;

use OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM\LoadUnitsAndFreightClassesData as BaseLoadUnitData;

class LoadUnitsAndFreightClassesData extends BaseLoadUnitData
{
    /** @var array */
    protected $weightUnits = [
        ['code' => 'demo_lbs', 'conversion_rates' => []],
    ];

    /** @var array */
    protected $lengthUnits = [
        ['code' => 'demo_cm', 'conversion_rates' => []],
    ];

    /** @var array */
    protected $freightClasses = [
        ['code' => 'demo_parcel'],
        ['code' => 'demo_class50'],
        ['code' => 'demo_class55'],
    ];
}
```

Add translations to added units:
```yml
orob2b:
    weight_unit.demo_lbs:
        label:
            full: demo_pound
            full_plural: demo_pounds
            short: dlbs
            short_plural: dlbs
        value:
            full: '{0} none|{1} %count% pound|]1,Inf] %count% demo_pounds'
            short: '{0} none|{1} %count% demo_lbs|]1,Inf] %count% demo_lbs'

    length_unit.demo_cm:
        label:
            full: demo_centimeter
            full_plural: demo_centimeters
            short: dcm
            short_plural: dcm

    freight_class.demo_parcel:
        label:
            full: demo_parcel
            full_plural: demo_parcels
            short: dpel
            short_plural: dpels

    freight_class.demo_class50:
        label:
            full: demo_class50
            full_plural: demo_class50
            short: dc50
            short_plural: dc50

    freight_class.demo_class55:
        label:
            full: demo_class55
            full_plural: demo_class55
            short: dc55
            short_plural: dc55
```

Clear cache `app/console cache:clear`

Load migration `app/console oro:migration:data:load`

Activate new units and classes on system configuration `System -> Configuration -> Commerce -> Shipping -> Shipping Options`.

All examples are available here [Demo Extension](https://github.com/laboro/dev/pull/386)
